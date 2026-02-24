<?php

namespace App\Services\Transparency;

use App\Exceptions\DataIntegrityException;
use App\Models\PublicationArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImmutableArchiveService
{
    public function lockPublication(string $publicationId): PublicationArchive
    {
        $publication = PublicationArchive::findOrFail($publicationId);

        if ($publication->is_locked) {
            throw new DataIntegrityException("Publication [{$publicationId}] is already locked.");
        }

        if (Storage::exists($publication->file_path)) {
            $hash = hash_file('sha256', Storage::path($publication->file_path));
            $publication->file_hash = $hash;
        }

        $publication->is_locked = true;
        $publication->save();

        Log::info('Publication locked', ['publication_id' => $publicationId, 'hash' => $publication->file_hash]);

        return $publication;
    }

    public function verifyIntegrity(string $publicationId): bool
    {
        $publication = PublicationArchive::findOrFail($publicationId);

        if (!$publication->file_hash) {
            throw new DataIntegrityException("No stored hash for publication [{$publicationId}].");
        }

        if (!Storage::exists($publication->file_path)) {
            throw new DataIntegrityException("File not found for publication [{$publicationId}].");
        }

        $currentHash = hash_file('sha256', Storage::path($publication->file_path));

        if ($currentHash !== $publication->file_hash) {
            throw new DataIntegrityException(
                "File integrity mismatch for publication [{$publicationId}]. File may have been tampered with."
            );
        }

        return true;
    }
}
