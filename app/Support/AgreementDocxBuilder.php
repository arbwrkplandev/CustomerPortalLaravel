<?php

namespace App\Support;

use Carbon\CarbonInterface;

class AgreementDocxBuilder
{
    public function buildFromHtml(string $htmlContent): string
    {
        return $this->buildDocx($htmlContent);
    }

    public function buildFromHtmlWithSignature(
        string $htmlContent,
        string $signaturePngBytes,
        string $signerName,
        CarbonInterface $signedAt
    ): string {
        return $this->buildDocx($htmlContent, [
            'bytes' => $signaturePngBytes,
            'signer_name' => $signerName,
            'signed_at' => $signedAt,
        ]);
    }

    private function buildDocx(string $htmlContent, ?array $signature = null): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive extension is required for DOCX generation.');
        }

        $hasSignature = is_array($signature)
            && isset($signature['bytes'])
            && is_string($signature['bytes'])
            && $signature['bytes'] !== '';

        $signatureRelationId = $hasSignature ? 'rIdSignatureImage1' : null;
        $documentXml = $this->buildDocumentXml($htmlContent, $signature, $signatureRelationId);

        $tmpPath = tempnam(sys_get_temp_dir(), 'wrkplan_agreement_');
        if ($tmpPath === false) {
            throw new \RuntimeException('Unable to create temporary file for DOCX build.');
        }

        $docxPath = $tmpPath . '.docx';
        @unlink($tmpPath);

        $zip = new \ZipArchive();
        if ($zip->open($docxPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create DOCX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($hasSignature));
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml($hasSignature, $signatureRelationId));
        $zip->addFromString('word/styles.xml', $this->stylesXml());

        if ($hasSignature) {
            $zip->addFromString('word/media/signature.png', $signature['bytes']);
        }

        if ($zip->close() === false) {
            @unlink($docxPath);
            throw new \RuntimeException('Failed to finalize DOCX archive.');
        }

        $bytes = @file_get_contents($docxPath);
        @unlink($docxPath);

        if (!is_string($bytes) || $bytes === '') {
            throw new \RuntimeException('Unable to read generated DOCX bytes.');
        }

        return $bytes;
    }

    private function buildDocumentXml(string $htmlContent, ?array $signature, ?string $signatureRelationId): string
    {
        $bodyXml = $this->convertHtmlToWordBodyXml($htmlContent);
        if ($signatureRelationId !== null && is_array($signature)) {
            $bodyXml .= $this->buildSignatureBlockXml(
                $signatureRelationId,
                (string) ($signature['signer_name'] ?? 'Customer'),
                $signature['signed_at'] ?? null,
                (string) ($signature['bytes'] ?? '')
            );
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
            . ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"'
            . ' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
            . ' xmlns:v="urn:schemas-microsoft-com:vml"'
            . ' xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"'
            . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:w10="urn:schemas-microsoft-com:office:word"'
            . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"'
            . ' xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml"'
            . ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"'
            . ' xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"'
            . ' xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"'
            . ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
            . ' mc:Ignorable="w14 w15 wp14">'
            . '<w:body>' . $bodyXml
            . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/>'
            . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>'
            . '<w:cols w:space="708"/><w:docGrid w:linePitch="360"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function buildSignatureBlockXml(
        string $relationId,
        string $signerName,
        mixed $signedAt,
        string $signatureBytes
    ): string {
        $safeSigner = htmlspecialchars(trim($signerName) === '' ? 'Customer' : $signerName, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $signedAtText = $signedAt instanceof CarbonInterface
            ? $signedAt->format('F d, Y h:i A')
            : now()->format('F d, Y h:i A');
        $safeSignedAt = htmlspecialchars($signedAtText, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        [$cx, $cy] = $this->resolveImageExtents($signatureBytes);

        $signatureTitle = htmlspecialchars('Signature image', ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $drawingXml = '<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="101" name="CustomerSignature" descr="' . $signatureTitle . '"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="Signature"/>'
            . '<pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $relationId . '"/>'
            . '<a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr>'
            . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
            . '</a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';

        return '<w:p><w:r><w:rPr><w:b/></w:rPr><w:t xml:space="preserve">Signature Block</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t xml:space="preserve">Signed By: ' . $safeSigner . '</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t xml:space="preserve">Signed At: ' . $safeSignedAt . '</w:t></w:r></w:p>'
            . $drawingXml;
    }

    private function resolveImageExtents(string $imageBytes): array
    {
        $width = 560;
        $height = 180;

        $size = @getimagesizefromstring($imageBytes);
        if (is_array($size) && isset($size[0], $size[1]) && $size[0] > 0 && $size[1] > 0) {
            $srcWidth = (float) $size[0];
            $srcHeight = (float) $size[1];
            $scale = min($width / $srcWidth, $height / $srcHeight, 1.0);

            $width = (int) max(120, round($srcWidth * $scale));
            $height = (int) max(60, round($srcHeight * $scale));
        }

        $emuPerPixel = 9525;

        return [$width * $emuPerPixel, $height * $emuPerPixel];
    }

    private function convertHtmlToWordBodyXml(string $htmlContent): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div>' . $htmlContent . '</div>';
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root instanceof \DOMElement) {
            return '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
        }

        $paragraphs = [];
        foreach ($root->childNodes as $node) {
            $this->appendHtmlNodeAsWordParagraphs($node, $paragraphs, []);
        }

        if ($paragraphs === []) {
            $paragraphs[] = '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
        }

        return implode('', $paragraphs);
    }

    private function appendHtmlNodeAsWordParagraphs(\DOMNode $node, array &$paragraphs, array $marks): void
    {
        if ($node instanceof \DOMText) {
            $text = trim($node->textContent);
            if ($text !== '') {
                $paragraphs[] = $this->buildWordParagraphXml('<w:r><w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r>');
            }
            return;
        }

        if (!$node instanceof \DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['p', 'div', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            $runs = $this->convertHtmlChildrenToWordRuns($node, $marks);
            $paragraphs[] = $this->buildWordParagraphXml($runs === '' ? '<w:r><w:t xml:space="preserve"></w:t></w:r>' : $runs, $tag, $node);
            return;
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $index = 1;
            foreach ($node->childNodes as $liNode) {
                if (!$liNode instanceof \DOMElement || strtolower($liNode->tagName) !== 'li') {
                    continue;
                }

                $prefix = $tag === 'ol' ? $index . '. ' : '- ';
                $runs = '<w:r><w:t xml:space="preserve">' . htmlspecialchars($prefix, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r>'
                    . $this->convertHtmlChildrenToWordRuns($liNode, $marks);
                $paragraphs[] = $this->buildWordParagraphXml($runs, 'li', $liNode);
                $index++;
            }
            return;
        }

        $runs = $this->convertHtmlChildrenToWordRuns($node, $marks);
        if ($runs !== '') {
            $paragraphs[] = $this->buildWordParagraphXml($runs);
        }
    }

    private function convertHtmlChildrenToWordRuns(\DOMNode $parent, array $marks): string
    {
        $runs = '';
        foreach ($parent->childNodes as $node) {
            $runs .= $this->convertHtmlNodeToWordRuns($node, $marks);
        }

        return $runs;
    }

    private function convertHtmlNodeToWordRuns(\DOMNode $node, array $marks): string
    {
        if ($node instanceof \DOMText) {
            $text = $node->textContent;
            if ($text === '') {
                return '';
            }

            return $this->buildWordRunXml($text, $marks);
        }

        if (!$node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'br') {
            return '<w:r><w:br/></w:r>';
        }

        $nextMarks = $marks;
        if (in_array($tag, ['strong', 'b'], true)) {
            $nextMarks['bold'] = true;
        }
        if (in_array($tag, ['em', 'i'], true)) {
            $nextMarks['italic'] = true;
        }
        if ($tag === 'u') {
            $nextMarks['underline'] = true;
        }

        return $this->convertHtmlChildrenToWordRuns($node, $nextMarks);
    }

    private function buildWordRunXml(string $text, array $marks): string
    {
        if ($text === '') {
            return '';
        }

        $safe = htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $props = '';
        if (!empty($marks['bold'])) {
            $props .= '<w:b/>';
        }
        if (!empty($marks['italic'])) {
            $props .= '<w:i/>';
        }
        if (!empty($marks['underline'])) {
            $props .= '<w:u w:val="single"/>';
        }

        $runProps = $props === '' ? '' : '<w:rPr>' . $props . '</w:rPr>';

        return '<w:r>' . $runProps . '<w:t xml:space="preserve">' . $safe . '</w:t></w:r>';
    }

    private function buildWordParagraphXml(string $runsXml, string $tag = 'p', ?\DOMElement $source = null): string
    {
        $pProps = '';

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $pProps .= '<w:pStyle w:val="Heading' . $matches[1] . '"/>';
        }

        if ($source instanceof \DOMElement) {
            $className = ' ' . strtolower((string) $source->getAttribute('class')) . ' ';
            if (str_contains($className, ' ql-align-center ')) {
                $pProps .= '<w:jc w:val="center"/>';
            } elseif (str_contains($className, ' ql-align-right ')) {
                $pProps .= '<w:jc w:val="right"/>';
            } elseif (str_contains($className, ' ql-align-justify ')) {
                $pProps .= '<w:jc w:val="both"/>';
            }
        }

        $pPrXml = $pProps === '' ? '' : '<w:pPr>' . $pProps . '</w:pPr>';

        return '<w:p>' . $pPrXml . $runsXml . '</w:p>';
    }

    private function contentTypesXml(bool $withSignatureImage): string
    {
        $imageDefault = $withSignatureImage
            ? '<Default Extension="png" ContentType="image/png"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $imageDefault
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function documentRelsXml(bool $withSignatureImage, ?string $signatureRelationId): string
    {
        $imageRel = ($withSignatureImage && $signatureRelationId !== null)
            ? '<Relationship Id="' . $signatureRelationId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/signature.png"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $imageRel
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
            . '<w:name w:val="Normal"/>'
            . '<w:qFormat/>'
            . '</w:style>'
            . '</w:styles>';
    }
}
