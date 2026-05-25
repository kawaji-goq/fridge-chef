<?php

namespace App\Livewire\Proposals;

use App\Jobs\GenerateProposalJob;
use App\Models\InventoryItem;
use App\Models\Proposal;
use App\Models\ProposalCandidate;
use App\Services\Adoptions\AdoptionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProposalPage extends Component
{
    public ?string $proposalRequestId = null;

    public ?string $proposalId = null;

    public string $proposalState = 'idle'; // idle | queued | completed | adopted

    public ?string $adoptedRecipeTitle = null;

    public array $adoptedConsumed = [];

    public array $adoptedShortages = [];

    /** @var string[] 「絶対使いたい」と選んだ ingredient_id のリスト */
    public array $mustUseIngredientIds = [];

    public function toggleMustUse(string $ingredientId): void
    {
        if (in_array($ingredientId, $this->mustUseIngredientIds, true)) {
            $this->mustUseIngredientIds = array_values(array_diff($this->mustUseIngredientIds, [$ingredientId]));
        } else {
            $this->mustUseIngredientIds[] = $ingredientId;
        }
    }

    #[Computed]
    public function inventoryItems(): Collection
    {
        return InventoryItem::with(['ingredient.localizations', 'unit'])
            ->where('user_id', Auth::id())
            ->orderByRaw('expires_at IS NULL, expires_at ASC')
            ->orderBy('created_at')
            ->get();
    }

    public function request(): void
    {
        $this->proposalRequestId = (string) Str::uuid();
        $this->proposalId = null;
        $this->proposalState = 'queued';

        GenerateProposalJob::dispatch(Auth::id(), $this->proposalRequestId, $this->mustUseIngredientIds);
    }

    public function poll(): void
    {
        if ($this->proposalState !== 'queued' || ! $this->proposalRequestId) {
            return;
        }
        $cached = cache()->get('proposal_request:'.$this->proposalRequestId);
        if (is_array($cached) && ($cached['status'] ?? '') === 'completed') {
            $this->proposalId = $cached['proposal_id'];
            $this->proposalState = 'completed';
        }
    }

    #[Computed]
    public function proposal(): ?Proposal
    {
        if (! $this->proposalId) {
            return null;
        }

        return Proposal::with([
            'candidates' => fn ($q) => $q->orderBy('rank'),
            'candidates.recipe.ingredients.ingredient.localizations',
            'candidates.recipe.ingredients.unit',
            'candidates.recipe.tags',
            'candidates.recipe.nutrientValues.nutrient',
        ])->find($this->proposalId);
    }

    public function adopt(string $candidateId, AdoptionService $service): void
    {
        $candidate = ProposalCandidate::with(['recipe.ingredients.ingredient', 'recipe.ingredients.unit'])
            ->where('id', $candidateId)
            ->where('proposal_id', $this->proposalId)
            ->firstOrFail();

        $result = $service->adopt(Auth::user(), $candidate);

        $this->adoptedRecipeTitle = $candidate->recipe?->title ?? ($candidate->recipe_snapshot['title'] ?? '?');
        $this->adoptedConsumed = $result['consumed'];
        $this->adoptedShortages = $result['shortages'];
        $this->proposalState = 'adopted';
    }

    public function resetProposal(): void
    {
        $this->proposalRequestId = null;
        $this->proposalId = null;
        $this->proposalState = 'idle';
        $this->adoptedRecipeTitle = null;
        $this->adoptedConsumed = [];
        $this->adoptedShortages = [];
        $this->mustUseIngredientIds = [];
    }

    public function render()
    {
        return view('livewire.proposals.proposal-page');
    }
}
