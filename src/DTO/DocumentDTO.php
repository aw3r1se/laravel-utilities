<?php

namespace Aw3r1se\UtilityTraits\DTO;

class DocumentDTO
{
    public string $source;
    public string $filename = '';
    public string $folder = '';
    public string $user_name = '';
    public ?int $sort = null;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function toArray(): array
    {
        $result = [
            'file_name' => $this->filename,
            'folder' => $this->folder,
        ];

        if ($this->user_name) {
            $result['user_name'] = $this->user_name;
        }

        if ($this->sort) {
            $result['sort'] = $this->sort;
        }

        return $result;
    }
}
