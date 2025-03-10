<?php

namespace App\Http\Controllers;

use App\Models\Definition;
use App\Models\Entry;
use App\Models\Example;
use Illuminate\Http\JsonResponse;

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
}
