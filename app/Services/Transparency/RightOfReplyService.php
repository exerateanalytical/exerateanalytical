<?php

namespace App\Services\Transparency;

use App\Models\RightOfReplySubmission;
use Illuminate\Support\Facades\Log;

class RightOfReplyService
{
    private array $prohibitedPatterns = [
        '/\b(vote|campaign|elect|candidate)\b/i',
    ];

    public function validateSubmission(array $data): array
    {
        $issues = [];

        foreach ($this->prohibitedPatterns as $pattern) {
            if (preg_match($pattern, $data['response_text'] ?? '')) {
                $issues[] = 'Response contains political campaigning language.';
                break;
            }
        }

        if (empty($data['response_text'])) {
            $issues[] = 'Response text is required.';
        }

        return $issues;
    }

    public function approve(RightOfReplySubmission $submission, string $reviewerId): RightOfReplySubmission
    {
        $submission->status = 'published';
        $submission->reviewed_by = $reviewerId;
        $submission->reviewed_at = now();
        $submission->save();

        Log::info('Right-of-reply approved and published', ['submission_id' => $submission->id]);

        return $submission;
    }

    public function reject(RightOfReplySubmission $submission, string $reviewerId): RightOfReplySubmission
    {
        $submission->status = 'rejected';
        $submission->reviewed_by = $reviewerId;
        $submission->reviewed_at = now();
        $submission->save();

        Log::info('Right-of-reply rejected', ['submission_id' => $submission->id]);

        return $submission;
    }
}
