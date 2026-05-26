<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Proposals\ProposalGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateProposalJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(
        public readonly string $userId,
        public readonly string $proposalRequestId,
        public readonly array $mustUseIngredientIds = [],
    ) {}

    public function handle(ProposalGenerator $generator): void
    {
        $user = User::findOrFail($this->userId);
        $proposal = $generator->generate($user, $this->mustUseIngredientIds);

        cache()->put(
            'proposal_request:'.$this->proposalRequestId,
            ['proposal_id' => $proposal->id, 'status' => 'completed'],
            now()->addMinutes(10)
        );
    }
}
