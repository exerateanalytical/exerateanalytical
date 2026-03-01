<?php

namespace App\Http\Resources\Governance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'governance_action_id' => $this->governance_action_id,
            'institution_name'     => $this->institution_name,
            'assigned_by'          => $this->assignedBy?->name ?? null,
            'action_type'          => $this->action?->action_type ?? null,
            'status'               => $this->status,
            'progress_percent'     => $this->progress_percent,
            'notes'                => $this->notes,
            'acknowledged_at'      => $this->acknowledged_at?->toIso8601String(),
            'completed_at'         => $this->completed_at?->toIso8601String(),
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
