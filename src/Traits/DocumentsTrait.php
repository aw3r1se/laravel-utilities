<?php

namespace Aw3r1se\LaravelUtilities\Traits;

use Aw3r1se\LaravelUtilities\DTO\DocumentDTO;
use Aw3r1se\LaravelUtilities\Models\Document;
use Aw3r1se\LaravelUtilities\Services\DocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait DocumentsTrait
{
    use MediaTrait;

    public array $uploadDocuments = [];
    public array $updateDocuments = [];
    public array $deleteDocuments = [];

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'model');
    }

    public function fillDocumentsTrait(array $attributes): void
    {
        if (!empty($attributes['uploadDocuments'])) {
            $this->uploadDocuments = $attributes['uploadDocuments'];
        }

        if (!empty($attributes['updateDocuments'])) {
            $this->updateDocuments = $attributes['updateDocuments'];
        }

        if (!empty($attributes['deleteDocuments'])) {
            $this->deleteDocuments = $attributes['deleteDocuments'];
        }
    }

    public static function bootDocumentsTrait(): void
    {
        static::saved(function (Model $model) {
            foreach ($model->uploadDocuments ?? [] as $item) {
                $model->addDocumentFromRequest($item);
            }

            foreach ($model->updateDocuments ?? [] as $item) {
                $model->documents()
                    ->where('id', (int)$item['id'])
                    ->update($item);
            }

            if ($model->deleteDocuments) {
                $model->documents()
                    ->whereIn('id', $model->deleteDocuments)
                    ->each(fn ($doc) => $doc->delete());
            }
        });

        static::deleting(function (Model $model) {
            $model->documents()->delete();
            $folder = DocumentService::pathGenerate($model);
            Storage::disk('public')->deleteDirectory($folder);
        });
    }

    public function addDocumentFromRequest($document): Model
    {
        $upload = $document['upload'];
        $documentDTO = new DocumentDTO($upload);
        $documentDTO->filename = DocumentService::fileNameGenerate($upload);
        $documentDTO->sort = $document['sort'] ?? null;
        $documentDTO->user_name = $document['user_name'] ?? '';

        return $this->addDocument($documentDTO);
    }

    public function addDocumentFromPath(string $file): Model
    {
        $documentDTO = new DocumentDTO($file);
        $documentDTO->filename = mb_strtolower(basename($file));

        return $this->addDocument($documentDTO);
    }

    public function addDocument(DocumentDTO $documentDTO): Model
    {
        $documentDTO->folder = DocumentService::createFolder($this);
        $documentDTO = DocumentService::saveDocument($documentDTO);

        // Обновляем время изменение сущности, предварительно проверив что сущность не обновилась только что
        // чтобы избежать лишнего запроса в базу
        if ($this->updated_at < now()->subMinute()) {
            $this->setUpdatedAt(now())->saveQuietly();
        }

        return $this->documents()
            ->create($documentDTO->toArray());
    }
}
