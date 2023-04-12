<?php

namespace Aw3r1se\UtilityTraits\Services;

use Aw3r1se\UtilityTraits\DTO\DocumentDTO;
use Illuminate\Support\Facades\File;

class DocumentService extends MediaService
{
    public const DOCUMENTS = 'documents';

    protected static string $type = self::DOCUMENTS;

    public static function saveDocument(DocumentDTO $documentDTO): DocumentDTO
    {
        $path = $documentDTO->folder . $documentDTO->filename;

        $index = 1;
        $originalFilename = $documentDTO->filename;
        while (self::getDisk()->exists($path)) {
            $index++;
            $documentDTO->filename = self::thumbNameGenerate($originalFilename, $index);
            $path = $documentDTO->folder . $documentDTO->filename;
        }
        File::copy($documentDTO->source, self::getDisk()->path($path));

        return $documentDTO;
    }
}
