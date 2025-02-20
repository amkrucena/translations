<?php

namespace Netcore\Translator\PassThroughs\Translation;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Netcore\Translator\PassThroughs\PassThrough;
use Netcore\Translator\Models\Translation;
use Netcore\Translator\Models\Language;
use Throwable;

class Import extends PassThrough
{

    /**
     *
     * Counter to give feedback to user
     *
     * @var int
     */
    private $existingKeysCount = 0;

    /**
     * 1. Parse excel file and create a collection of all entries in that file
     * 2. Determine which keys already exist in DB via some collection magic (dont fire endless queries)
     * 3. Delete from DB those keys that already exist there
     * 4. Mass-insert all entries that are in excel
     *
     * 1. Parse excel file and create a collection of all entries in that file
     * 2. Determine which keys do not exist in DB via some collection magic (dont fire endless queries)
     * 3. Mass-insert all new keys
     * 4. Show confirmation message with the following stats:
     *    1) x new keys were found. These were added.
     *    2) x keys already exist. These were not changed.
     *
     *
     * @param $allData
     * @param bool $flashMessage
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function process($allData, bool $flashMessage = true): bool
    {
        DB::transaction(function () use ($allData, $flashMessage) {

            // 1.
            $parsedTranslations = $this->parsedTranslations($allData);

            // 2.
            $newTranslations = $this->newTranslations($parsedTranslations);


            // 3.
            foreach (array_chunk($newTranslations, 300) as $chunk) {
                foreach($chunk as $item) {
                    $tr = Translation::where('key', $item['key'])->where('locale', $item['locale'])->where('group', $item['group'])->first();
                    if($tr) {
                        $tr->value = $item['value'];
                        $tr->save();
                    } else {
                        Translation::create($item);
                    }
                }
            }

            if ($flashMessage) {
                // 4.
                $localesCount = Language::pluck('iso_code')->count();
                $newKeysCount = round(count($newTranslations) / $localesCount);
                $existingKeysCount = round($this->existingKeysCount / $localesCount);
                $this->flashMessages($newKeysCount, $existingKeysCount);
            }
        });

        $this->flushCache();

        return true;
    }

    /**
     * @param $json
     * @return bool
     * @throws Exception
     */
    public function processJson($json): bool
    {

        $this->flushCache();

        return true;
    }

    /**
     * @param $allData
     * @return array
     */
    private function parsedTranslations($allData): array
    {
        $locales = Language::pluck('iso_code')->toArray();

        $parsedTranslations = [];

        foreach ($allData as $pageNr => $pageData) {

            // If this is not empty, it means we only have one sheet.
            // Otherwise, we have multiple sheets.
            $groupKey = Arr::get($pageData, 'key', '');

            if ($groupKey) {
                foreach ($allData as $row) {
                    $newItems = $this->translationsFromOneRow($row, $locales);
                    foreach ($newItems as $item) {
                        $parsedTranslations[] = $item;
                    }
                }
                break;
            } else {
                foreach ($pageData as $row) {
                    $newItems = $this->translationsFromOneRow($row, $locales);
                    foreach ($newItems as $item) {
                        $parsedTranslations[] = $item;
                    }
                }
            }
        }

        return $parsedTranslations;
    }

    /**
     * @param $row
     * @param $locales
     * @return Collection
     */
    private function translationsFromOneRow($row, $locales): Collection
    {
        $translations = collect();

        $groupKey = Arr::get($row, 'key', '');

        if (!$groupKey) {
            return $translations;
        }

        $firstDot = strpos($groupKey, '.');
        if ($firstDot === false) {
            return $translations;
        }

        $group = substr($groupKey, 0, $firstDot);
        $key = substr($groupKey, $firstDot + 1);

        foreach ($row as $fieldname => $value) {
            if ($fieldname AND in_array($fieldname, $locales)) {

                $data = ([
                    'locale' => $fieldname,
                    'group'  => $group,
                    'key'    => $key,
                    'value'  => Arr::get($row, $fieldname, ''),
                ]);

                if (function_exists('domain_id')) {
                    $data['domain_id'] = domain_id();
                }

                $translations->push($data);
            }
        }

        return $translations;
    }

    /**
     * @param $parsedTranslations
     * @return array
     */
    private function newTranslations($parsedTranslations): array
    {
        $overrideOldTranslations = config('translations.override_old_translations', false);

        $fieldsToSelect = [
            'locale',
            'group',
            'key'
        ];

        $callableDomainId = is_callable('domain_id');

        if ($callableDomainId) {
            $fieldsToSelect[] = 'domain_id';
        }

        $existing = Translation::select($fieldsToSelect)->get();
        $newTranslations = [];
        $exists = false;

        foreach ($parsedTranslations as $parsedTranslation) {

            if (!$overrideOldTranslations) {
                $existsQuery = $existing
                    ->where('locale', $parsedTranslation['locale'])
                    ->where('group', $parsedTranslation['group'])
                    ->where('key', $parsedTranslation['key']);

                if ($callableDomainId) {
                    $existsQuery = $existsQuery
                        ->where('domain_id', domain_id());
                }

                $exists = $existsQuery->first();
            }

            if (!$exists) {
                $newTranslations[] = $parsedTranslation;
            } else {
                $this->existingKeysCount++;
            }
        }

        return $newTranslations;
    }

    /**
     * @param $newKeysCount
     * @param $existingKeysCount
     */
    private function flashMessages($newKeysCount, $existingKeysCount): void
    {
        $response = [];
        $uiTranslations = config('translations.ui_translations.translations', []);
        $overrideOldTranslations = config('translations.override_old_translations', false);

        if ($newKeysCount) {
            if ($overrideOldTranslations) {
                $xNewKeysWereFound = Arr::get(
                    $uiTranslations,
                    'x_keys_were_modified',
                    ':count keys modified in the system!'
                );
            } else {
                $xNewKeysWereFound = Arr::get(
                    $uiTranslations,
                    'x_new_keys_were_found',
                    ':count new keys added to the system!'
                );
            }

            $response[] = str_replace(':count', $newKeysCount, $xNewKeysWereFound);
        } else {
            $noNewKeysWereFound = Arr::get(
                $uiTranslations,
                'new_keys_were_not_found',
                'No new keys to add! Doing nothing.'
            );
            $response[] = $noNewKeysWereFound;
        }

        if ($existingKeysCount) {
            $xKeysAlreadyExist = Arr::get(
                $uiTranslations,
                'x_keys_already_exist',
                ':count keys already exist. These were not changed.'
            );
            $response[] = str_replace(':count', $existingKeysCount, $xKeysAlreadyExist);
        }

        session()->flash('success', $response);
    }

    public function flushCache(): void
    {
        $keyToForget = 'translations';
        $function = config('translations.translations_key_in_cache');
        if ($function AND function_exists($function)) {
            $keyToForget = $function();
        }

        cache()->forget($keyToForget);
    }

}