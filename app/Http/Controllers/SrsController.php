<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SrsController extends Controller
{
    /**
     * Returns cards that are due for review now.
     * @return JsonResponse
     */
    public function getDueCards(): JsonResponse
    {
        $cards = Card::where('due', '<=', now())->get();
        return response()->json($cards);
    }

    /**
     * Updates an existing card in the database after it is reviewed.
     * @param Request $request The request object containing the updated card.
     * @param int $id The ID of the card to update.
     * @return JsonResponse
     */
    public function updateCard(Request $request, int $id): JsonResponse
    {
        // validate the request
        $validated = $request->validate([
            'due' => 'required|date',
            'stability' => 'required|integer',
            'difficulty' => 'required|integer',
            'elapsed_days' => 'required|integer',
            'scheduled_days' => 'required|integer',
            'reps' => 'required|integer',
            'lapses' => 'required|integer',
            'state' => 'required|in:new,learning,review,relearning',
            'last_review' => 'nullable|date',
        ]);

        // update the card in the database
        $card = Card::find($id);
        return $this->saveCard($validated, $card);
    }

    /**
     * Adds a new card to the database.
     * @param Request $request The request object containing a new card.
     * @return JsonResponse
     */
    public function addCard(Request $request): JsonResponse
    {
        // validate the request
        $validated = $request->validate([
            'entry_id' => 'required|integer',
            'due' => 'required|date',
            'stability' => 'required|integer',
            'difficulty' => 'required|integer',
            'elapsed_days' => 'required|integer',
            'scheduled_days' => 'required|integer',
            'reps' => 'required|integer',
            'lapses' => 'required|integer',
            'state' => 'required|in:new,learning,review,relearning',
            'last_review' => 'nullable|date',
        ]);

        // insert the card into the database
        $card = new Card();
        $card->entry_id = $validated['entry_id'];
        return $this->saveCard($validated, $card);
    }

    /**
     * @param array $validated
     * @param Collection|Card|null $card
     * @return JsonResponse
     */
    public function saveCard(array $validated, Collection|Card|null $card): JsonResponse
    {
        $card->due = $validated['due'];
        $card->stability = $validated['stability'];
        $card->difficulty = $validated['difficulty'];
        $card->elapsed_days = $validated['elapsed_days'];
        $card->scheduled_days = $validated['scheduled_days'];
        $card->reps = $validated['reps'];
        $card->lapses = $validated['lapses'];
        $card->state = $validated['state'];
        $card->last_review = $validated['last_review'];
        $card->save();
        return response()->json($card);
    }
}
