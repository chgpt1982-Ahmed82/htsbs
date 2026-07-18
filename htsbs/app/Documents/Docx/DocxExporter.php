<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentXml.php';
require_once __DIR__ . '/StylesXml.php';
require_once __DIR__ . '/ContentTypes.php';
require_once __DIR__ . '/Relationships.php';
require_once __DIR__ . '/ZipBuilder.php';

class DocxExporter
{
    /**
     * إنشاء ملف DOCX من HTML
     *
     * @param string $fileName
     * @param string $html
     */
    public static function download(
        string $fileName,
        string $html
    ): void {

        $documentXml = DocumentXml::build($html);

        $stylesXml = StylesXml::build();

        $contentTypes = ContentTypes::build();

        $relationships = Relationships::build();

        $zip = new ZipBuilder();

        $zip->add(
            '[Content_Types].xml',
            $contentTypes
        );

        $zip->add(
            '_rels/.rels',
            $relationships
        );

        $zip->add(
            'word/document.xml',
            $documentXml
        );

        $zip->add(
            'word/styles.xml',
            $stylesXml
        );

        $zip->download($fileName);
    }
}