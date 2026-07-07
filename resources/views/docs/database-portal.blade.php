<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WrkPlan Database Docs</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet">
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        :root {
            --bg: #08111f;
            --surface: #10213d;
            --surface-2: #142a4b;
            --text: #dce7ff;
            --muted: #9db1d8;
            --border: rgba(148, 163, 184, 0.28);
            --accent: #38bdf8;
            --accent-2: #34d399;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 15% 15%, #1e3a8a55 0%, transparent 40%),
                        radial-gradient(circle at 80% 20%, #0ea5e955 0%, transparent 35%),
                        linear-gradient(135deg, #08111f, #0a1426 60%, #08111f);
            min-height: 100vh;
        }
        .wrap {
            display: grid;
            grid-template-columns: 320px 1fr 360px;
            gap: 14px;
            padding: 16px;
            max-width: 1700px;
            margin: 0 auto;
        }
        .card {
            background: rgba(16, 33, 61, 0.88);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(2, 6, 23, 0.45);
            backdrop-filter: blur(10px);
        }
        .left, .right { padding: 14px; height: calc(100vh - 32px); overflow: auto; }
        .main { padding: 10px; height: calc(100vh - 32px); }
        .title {
            margin: 0 0 12px;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .subtitle { margin: 0 0 10px; color: var(--muted); font-size: .82rem; }
        .search {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #0a1830;
            color: var(--text);
            padding: 10px 12px;
            margin-bottom: 10px;
        }
        .table-list { display: flex; flex-direction: column; gap: 8px; }
        .table-item {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px;
            cursor: pointer;
            transition: all .2s ease;
            background: #0d1a33;
        }
        .table-item:hover { transform: translateY(-2px); border-color: #38bdf855; }
        .table-item.active { border-color: var(--accent); background: #0c203f; }
        .table-item .name { font-weight: 700; font-size: .92rem; }
        .table-item .meta { color: var(--muted); font-size: .75rem; margin-top: 4px; }
        #graph {
            width: 100%;
            height: 100%;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: linear-gradient(145deg, #09172e, #0b1b35);
        }
        .group {
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }
        .kv { display: grid; grid-template-columns: 90px 1fr; gap: 6px; font-size: .82rem; }
        .muted { color: var(--muted); }
        .cols { display: flex; flex-direction: column; gap: 8px; }
        details.col {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: #0c1b34;
            padding: 8px 10px;
        }
        details.col summary {
            cursor: pointer;
            font-size: .85rem;
            font-weight: 600;
        }
        .chip {
            display: inline-block;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: .7rem;
            margin-left: 6px;
            color: #031b16;
            background: linear-gradient(135deg, #6ee7b7, #34d399);
        }
        .legend {
            display: flex;
            gap: 8px;
            font-size: .75rem;
            color: var(--muted);
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .error {
            border: 1px solid #fca5a5;
            color: #fee2e2;
            background: #7f1d1d66;
            border-radius: 10px;
            padding: 10px;
            font-size: .84rem;
        }
        @media (max-width: 1280px) {
            .wrap { grid-template-columns: 1fr; }
            .left, .main, .right { height: auto; min-height: 420px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <aside class="card left">
            <h1 class="title">Database Explorer <span class="chip" id="driverChip">loading</span></h1>
            <p class="subtitle">Interactive ER graph with searchable entities, relationships, indexes, constraints, triggers, views, and routines.</p>
            <input id="search" class="search" type="text" placeholder="Search table...">
            <div id="tableList" class="table-list"></div>
            <div class="legend">
                <span><i class="dot" style="background:#38bdf8"></i>Table</span>
                <span><i class="dot" style="background:#f59e0b"></i>View</span>
                <span><i class="dot" style="background:#34d399"></i>FK link</span>
            </div>
        </aside>

        <main class="card main">
            <div id="graph"></div>
        </main>

        <aside class="card right">
            <h2 class="title">Entity Details</h2>
            <div id="details" class="muted">Select a table from the left panel or click a node in the graph.</div>
        </aside>
    </div>

    <script>
        let schema = null;
        let network = null;
        let selectedTable = null;

        const tableListEl = document.getElementById('tableList');
        const detailsEl = document.getElementById('details');
        const searchEl = document.getElementById('search');
        const driverChip = document.getElementById('driverChip');

        async function loadSchema() {
            const res = await fetch('{{ url('/docs/database/schema.json') }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) {
                throw new Error('Could not load schema metadata.');
            }
            schema = await res.json();
            driverChip.textContent = (schema.driver || 'db').toUpperCase();
            renderTableList(schema.tables || []);
            renderGraph();
            renderGlobalMeta();
        }

        function renderGlobalMeta() {
            const note = document.createElement('div');
            note.className = 'group muted';
            note.innerHTML = `
                <div class="kv"><strong>Connection</strong><span>${schema.connection_name || 'n/a'}</span></div>
                <div class="kv"><strong>Database</strong><span>${schema.database || 'n/a'}</span></div>
                <div class="kv"><strong>Configured</strong><span>${(schema.configured_connections || []).join(', ') || 'n/a'}</span></div>
                <div class="kv"><strong>Tables</strong><span>${(schema.tables || []).length}</span></div>
                <div class="kv"><strong>Views</strong><span>${(schema.views || []).length}</span></div>
                <div class="kv"><strong>Triggers</strong><span>${(schema.triggers || []).length}</span></div>
                <div class="kv"><strong>Routines</strong><span>${(schema.procedures || []).length}</span></div>
                <div class="kv"><strong>Generated</strong><span>${schema.generated_at || ''}</span></div>
            `;
            detailsEl.innerHTML = '';
            detailsEl.appendChild(note);
        }

        function renderTableList(tables) {
            tableListEl.innerHTML = '';
            tables.forEach((table) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'table-item';
                btn.dataset.table = table.name;
                btn.innerHTML = `
                    <div class="name">${table.name}</div>
                    <div class="meta">${table.columns.length} columns · ${table.foreign_keys.length} FK</div>
                `;
                btn.addEventListener('click', () => selectTable(table.name, true));
                tableListEl.appendChild(btn);
            });
        }

        function renderGraph() {
            const nodes = [];
            const edges = [];

            (schema.tables || []).forEach((table) => {
                nodes.push({
                    id: table.name,
                    label: table.name,
                    shape: 'box',
                    color: {
                        background: '#1d4ed8',
                        border: '#60a5fa',
                        highlight: { background: '#2563eb', border: '#93c5fd' }
                    },
                    font: { color: '#e5edff', face: 'Instrument Sans', size: 14 },
                    margin: 10,
                });
            });

            (schema.views || []).forEach((view) => {
                nodes.push({
                    id: 'view:' + view.name,
                    label: 'VIEW\n' + view.name,
                    shape: 'ellipse',
                    color: {
                        background: '#7c2d12',
                        border: '#f59e0b',
                        highlight: { background: '#9a3412', border: '#fbbf24' }
                    },
                    font: { color: '#fff7ed', face: 'Instrument Sans', size: 12 },
                });
            });

            (schema.relationships || []).forEach((rel, idx) => {
                edges.push({
                    id: rel.constraint || `rel_${idx}`,
                    from: rel.from,
                    to: rel.to,
                    label: rel.label,
                    arrows: 'to',
                    color: { color: '#34d399', highlight: '#6ee7b7' },
                    font: { align: 'middle', size: 10, color: '#86efac' },
                    smooth: { type: 'dynamic' },
                });
            });

            const data = { nodes: new vis.DataSet(nodes), edges: new vis.DataSet(edges) };
            const options = {
                autoResize: true,
                physics: {
                    stabilization: { iterations: 250 },
                    barnesHut: { gravitationalConstant: -7000, springLength: 170, springConstant: 0.015 }
                },
                interaction: { hover: true, zoomView: true, dragView: true, navigationButtons: true },
                nodes: { borderWidth: 1.5, shadow: true },
                edges: { width: 1.2, shadow: true },
            };

            const container = document.getElementById('graph');
            network = new vis.Network(container, data, options);

            network.on('click', (params) => {
                if (!params.nodes || !params.nodes.length) {
                    return;
                }
                const id = params.nodes[0];
                if (!id.startsWith('view:')) {
                    selectTable(id, false);
                }
            });
        }

        function selectTable(name, focusNetwork) {
            selectedTable = (schema.tables || []).find((t) => t.name === name) || null;
            document.querySelectorAll('.table-item').forEach((el) => {
                el.classList.toggle('active', el.dataset.table === name);
            });

            if (!selectedTable) {
                detailsEl.innerHTML = '<div class="muted">Table not found.</div>';
                return;
            }

            const indexesHtml = selectedTable.indexes.length
                ? selectedTable.indexes.map((idx) => `<li><strong>${idx.name}</strong> (${idx.unique ? 'unique' : 'non-unique'}) - ${idx.columns.join(', ')}</li>`).join('')
                : '<li>None</li>';

            const fkHtml = selectedTable.foreign_keys.length
                ? selectedTable.foreign_keys.map((fk) => `<li>${fk.column} -> ${fk.references_table}.${fk.references_column} <span class="muted">ON UPDATE ${fk.on_update} / ON DELETE ${fk.on_delete}</span></li>`).join('')
                : '<li>None</li>';

            const colsHtml = selectedTable.columns.map((col) => `
                <details class="col">
                    <summary>${col.name}${col.key === 'PRI' ? '<span class="chip">PK</span>' : ''}</summary>
                    <div class="kv muted" style="margin-top:8px">
                        <span>Type</span><span>${col.column_type}</span>
                        <span>Nullable</span><span>${col.nullable ? 'YES' : 'NO'}</span>
                        <span>Default</span><span>${col.default === null ? 'NULL' : col.default}</span>
                        <span>Extra</span><span>${col.extra || '—'}</span>
                    </div>
                </details>
            `).join('');

            detailsEl.innerHTML = `
                <div class="group">
                    <h3 style="margin:0 0 8px">${selectedTable.name}</h3>
                    <div class="kv muted">
                        <span>Columns</span><span>${selectedTable.columns.length}</span>
                        <span>Primary Keys</span><span>${selectedTable.primary_keys.join(', ') || '—'}</span>
                        <span>Indexes</span><span>${selectedTable.indexes.length}</span>
                        <span>Foreign Keys</span><span>${selectedTable.foreign_keys.length}</span>
                    </div>
                </div>
                <div class="group">
                    <strong>Columns</strong>
                    <div class="cols" style="margin-top:8px">${colsHtml}</div>
                </div>
                <div class="group">
                    <strong>Indexes</strong>
                    <ul class="muted" style="margin:8px 0 0 18px">${indexesHtml}</ul>
                </div>
                <div class="group">
                    <strong>Foreign Keys</strong>
                    <ul class="muted" style="margin:8px 0 0 18px">${fkHtml}</ul>
                </div>
            `;

            if (focusNetwork && network) {
                network.focus(name, { scale: 1.1, animation: { duration: 400, easingFunction: 'easeInOutCubic' } });
            }
        }

        searchEl.addEventListener('input', () => {
            const q = searchEl.value.trim().toLowerCase();
            document.querySelectorAll('.table-item').forEach((item) => {
                const visible = item.dataset.table.toLowerCase().includes(q);
                item.style.display = visible ? '' : 'none';
            });
        });

        loadSchema().catch((error) => {
            detailsEl.innerHTML = `<div class="error">${error.message}</div>`;
        });
    </script>
</body>
</html>
