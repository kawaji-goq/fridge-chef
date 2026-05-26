<?php

namespace App\Livewire\Settings;

use App\Models\Allergen;
use App\Models\IngredientAlias;
use App\Models\IngredientLocalization;
use App\Models\UserAllergy;
use App\Models\UserDislikedIngredient;
use App\Models\UserPreferenceTag;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsPage extends Component
{
    public int $householdAdults = 1;

    public int $householdChildren = 0;

    /** @var int[] */
    public array $allergenIds = [];

    /** @var string[] */
    public array $preferenceTags = [];

    /** @var string[] */
    public array $dislikedIngredientIds = [];

    public string $dislikeQuery = '';

    public bool $savedFlash = false;

    public function mount(): void
    {
        $user = Auth::user();
        $profile = $user->profile;
        if ($profile) {
            $this->householdAdults = (int) $profile->household_adults;
            $this->householdChildren = (int) $profile->household_children;
        }

        $this->allergenIds = $user->allergies()->pluck('allergen_id')->map(fn ($v) => (int) $v)->all();
        $this->preferenceTags = $user->preferenceTags()->pluck('tag')->all();
        $this->dislikedIngredientIds = $user->dislikedIngredients()->pluck('ingredient_id')->all();
    }

    #[Computed]
    public function allergens(): Collection
    {
        return Allergen::orderBy('id')->get();
    }

    #[Computed]
    public function preferenceOptions(): array
    {
        return [
            ['tag' => 'washoku', 'label' => '和食'],
            ['tag' => 'yoshoku', 'label' => '洋食'],
            ['tag' => 'chuka', 'label' => '中華'],
            ['tag' => 'italian', 'label' => 'イタリアン'],
            ['tag' => 'nabe', 'label' => '鍋'],
        ];
    }

    #[Computed]
    public function dislikeSuggestions(): array
    {
        $q = trim($this->dislikeQuery);
        if ($q === '') {
            return [];
        }

        $byLocalization = IngredientLocalization::query()
            ->where('locale', 'ja-JP')
            ->where('display_name', 'like', "%{$q}%")
            ->whereNotIn('ingredient_id', $this->dislikedIngredientIds)
            ->limit(8)
            ->get(['ingredient_id', 'display_name']);

        $byAlias = IngredientAlias::query()
            ->where('locale', 'ja-JP')
            ->where('alias', 'like', "%{$q}%")
            ->whereNotIn('ingredient_id', $this->dislikedIngredientIds)
            ->with(['ingredient.localizations' => fn ($q) => $q->where('locale', 'ja-JP')])
            ->limit(8)
            ->get();

        return $byLocalization->map(fn ($l) => [
            'id' => $l->ingredient_id,
            'name' => $l->display_name,
        ])->concat($byAlias->map(fn ($a) => [
            'id' => $a->ingredient_id,
            'name' => $a->ingredient->displayName(),
        ]))->unique('id')->values()->all();
    }

    #[Computed]
    public function dislikedNames(): array
    {
        if (empty($this->dislikedIngredientIds)) {
            return [];
        }
        $locs = IngredientLocalization::query()
            ->where('locale', 'ja-JP')
            ->whereIn('ingredient_id', $this->dislikedIngredientIds)
            ->get(['ingredient_id', 'display_name']);

        return $locs->map(fn ($l) => [
            'id' => $l->ingredient_id,
            'name' => $l->display_name,
        ])->all();
    }

    public function toggleAllergen(int $id): void
    {
        if (in_array($id, $this->allergenIds, true)) {
            $this->allergenIds = array_values(array_diff($this->allergenIds, [$id]));
        } else {
            $this->allergenIds[] = $id;
        }
    }

    public function togglePreference(string $tag): void
    {
        if (in_array($tag, $this->preferenceTags, true)) {
            $this->preferenceTags = array_values(array_diff($this->preferenceTags, [$tag]));
        } else {
            $this->preferenceTags[] = $tag;
        }
    }

    public function addDislike(string $ingredientId): void
    {
        if (! in_array($ingredientId, $this->dislikedIngredientIds, true)) {
            $this->dislikedIngredientIds[] = $ingredientId;
        }
        $this->dislikeQuery = '';
    }

    public function removeDislike(string $ingredientId): void
    {
        $this->dislikedIngredientIds = array_values(array_diff($this->dislikedIngredientIds, [$ingredientId]));
    }

    public function save(): void
    {
        $userId = Auth::id();

        DB::transaction(function () use ($userId) {
            UserProfile::updateOrCreate(
                ['user_id' => $userId],
                [
                    'household_adults' => max(1, $this->householdAdults),
                    'household_children' => max(0, $this->householdChildren),
                ]
            );

            UserAllergy::where('user_id', $userId)->delete();
            foreach ($this->allergenIds as $aid) {
                UserAllergy::create(['user_id' => $userId, 'allergen_id' => $aid, 'severity' => 'strict']);
            }

            UserPreferenceTag::where('user_id', $userId)->delete();
            foreach ($this->preferenceTags as $tag) {
                UserPreferenceTag::create(['user_id' => $userId, 'tag' => $tag, 'weight' => 1]);
            }

            UserDislikedIngredient::where('user_id', $userId)->delete();
            foreach ($this->dislikedIngredientIds as $iid) {
                UserDislikedIngredient::create(['user_id' => $userId, 'ingredient_id' => $iid]);
            }
        });

        $this->savedFlash = true;
    }

    public function render()
    {
        return view('livewire.settings.settings-page');
    }
}
