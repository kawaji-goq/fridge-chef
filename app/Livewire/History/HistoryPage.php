<?php

namespace App\Livewire\History;

use App\Models\Adoption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HistoryPage extends Component
{
    public function delete(string $adoptionId): void
    {
        Adoption::where('user_id', Auth::id())
            ->where('id', $adoptionId)
            ->delete();
        unset($this->adoptions);
    }

    #[Computed]
    public function adoptions(): Collection
    {
        return Adoption::with([
            'recipe.tags',
            'recipe.nutrientValues.nutrient',
            'inventoryUses.unit',
            'inventoryUses.inventoryItem.ingredient.localizations',
        ])
            ->where('user_id', Auth::id())
            ->orderByDesc('adopted_at')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.history.history-page');
    }
}
