<?php

namespace Modules\CupGdocs\Contracts;

interface IDrive
{
    // public function save(string $name, string $content, ?string $folderId = null): object;
    // public function update(string $name, string $content, ?string $folderId = null): object;
    // public function delete(string $name): void;
    public function getDocumentBody(string $itemId): string;
    public function saveFromModel(string $name, string $template, array $data, ?string $folderId = null): string|null;
    public function getPdf(string $itemId): string;
    public function createFolder(string $name, ?string $folderId = null): string;
    public function getFolders(string $path): array;
    public function getFolderId(string $path): string;
    public function deleteItem(string $itemId): void;
    public function getUrl(string $itemId): string;
}