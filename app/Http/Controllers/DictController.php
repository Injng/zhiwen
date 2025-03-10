<?php

namespace App\Http\Controllers;

use App\Models\Definition;
use App\Models\Entry;
use App\Models\Example;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DictController extends Controller
{
    /**
     * Returns dictionary entries for a given word.
     * @param string $word The word to get dictionary entries for.
     * @return JsonResponse
     */
    public function getEntry(string $word): JsonResponse
    {
        $entry = Entry::where('word', $word)->get();
        return response()->json($entry);
    }

    /**
     * Returns definitions for a given entry.
     * @param int $entryId The ID of the entry to get definitions for.
     * @return JsonResponse
     */
    public function getDefinitions(int $entryId): JsonResponse
    {
        $definitions = Definition::where('entry_id', $entryId)->get();
        return response()->json($definitions);
    }

    /**
     * Returns examples for a given definition.
     * @param int $definitionId The ID of the definition to get examples for.
     * @return JsonResponse
     */
    public function getExamples(int $definitionId): JsonResponse
    {
        $examples = Example::where('definition_id', $definitionId)->get();
        return response()->json($examples);
    }

    /**
     * Adds a new example to the database.
     * @param Request $request The request object containing a new example.
     * @return JsonResponse
     */
    public function addExample(Request $request): JsonResponse
    {
        // validate the request
        $validated = $request->validate([
            'definition_id' => 'required|integer',
            'sentence' => 'required|string',
        ]);

        // insert the example into the database
        $example = new Example();
        $example->definition_id = $validated['definition_id'];
        $example->sentence = $validated['sentence'];
        $example->save();
        return response()->json($example);
    }

    /**
     * Adds a new definition to the database.
     * @param Request $request The request object containing a new definition.
     * @return JsonResponse
     */
    public function addDefinition(Request $request): JsonResponse
    {
        // validate the request
        $validated = $request->validate([
            'entry_id' => 'required|integer',
            'part' => 'required|string',
            'definition' => 'required|string',
        ]);

        // insert the definition into the database
        $definition = new Definition();
        $definition->entry_id = $validated['entry_id'];
        $definition->part = $validated['part'];
        $definition->definition = $validated['definition'];
        $definition->save();
        return response()->json($definition);
    }

    public function addEntry(Request $request): JsonResponse
    {
        // validate the request
        $validated = $request->validate([
            'word' => 'required|string',
            'pinyin' => 'required|string',
        ]);

        // insert the entry into the database
        $entry = new Entry();
        $entry->word = $validated['word'];
        $entry->pinyin = $validated['pinyin'];
        $entry->save();
        return response()->json($entry);
    }
}
