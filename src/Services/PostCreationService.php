<?php

namespace WebBestPractice\Posts\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PostCreationService
{
    private array $requiredRequestFields = ['title', 'content', 'meta_title', 'meta_keywords', 'meta_description'];

    /**
     * Validate the request secret against the configured posts.secret value.
     *
     * @throws Exception With code 401 when the secret is missing or invalid.
     */
    public function verifySecret(string $secret): void
    {
        $expectedSecret = (string) config('posts.secret', '');

        if ($expectedSecret === '' || ! hash_equals($expectedSecret, $secret)) {
            throw new Exception('Unauthorized.', 401);
        }
    }

    /**
     * Build and persist a new post from validated request data.
     *
     * @param  array<string, mixed>  $validated  Request data without the secret key.
     * @throws Exception With code 400 for column or persistence errors, 401 for a missing model class.
     */
    public function create(array $validated): Model
    {
        $model = $this->resolveModel();
        $columnsToMap = collect(config('posts.map'));
        $existingColumns = $this->getTableColumns($model);

        $this->assertRequiredColumnsInMap($columnsToMap);
        $this->assertRequiredColumnsInDatabase($columnsToMap, $existingColumns);

        $data = $this->mapValidatedData($columnsToMap, $validated);
        $data = $this->attachImages($model, $validated, $data);

        return $model->create($data);
    }

    /**
     * Instantiate the Eloquent model defined in posts.class config.
     *
     * @throws Exception With code 401 when the configured class does not exist.
     */
    private function resolveModel(): Model
    {
        $class = config('posts.class');

        if (! class_exists($class)) {
            throw new Exception('Post class not found.', 401);
        }

        return new $class;
    }

    /**
     * Load all column names for the target model's database table.
     */
    private function getTableColumns(Model $model): Collection
    {
        return collect(Schema::getColumnListing($model->getTable()));
    }

    /**
     * Ensure every required request field is used as a mapping source in posts.map config.
     *
     * @throws Exception With code 400 when a required request field is not mapped.
     */
    private function assertRequiredColumnsInMap(Collection $columnsToMap): void
    {
        foreach ($this->requiredRequestFields as $requiredRequestField) {
            $isMapped = $columnsToMap->contains(
                fn (mixed $mapping) => $this->resolveSourceRequestField($mapping) === $requiredRequestField
            );

            if (! $isMapped) {
                throw new Exception("Request field '{$requiredRequestField}' is not mapped.", 400);
            }
        }
    }

    /**
     * Ensure database columns targeted by required request fields exist in the table.
     *
     * @throws Exception With code 400 when a mapped column is missing from the table.
     */
    private function assertRequiredColumnsInDatabase(Collection $columnsToMap, Collection $existingColumns): void
    {
        foreach ($this->requiredRequestFields as $requiredRequestField) {
            $targetColumns = $columnsToMap
                ->filter(fn (mixed $mapping) => $this->resolveSourceRequestField($mapping) === $requiredRequestField)
                ->keys();

            if ($targetColumns->isEmpty()) {
                throw new Exception("Request field '{$requiredRequestField}' is not mapped.", 400);
            }

            foreach ($targetColumns as $column) {
                if (! $existingColumns->contains($column)) {
                    throw new Exception("Column '{$column}' not found.", 400);
                }
            }
        }
    }

    /**
     * Resolve the request field a mapping reads from, if any.
     */
    private function resolveSourceRequestField(mixed $mapping): ?string
    {
        if (is_string($mapping) && $mapping !== 'date_now') {
            return $mapping;
        }

        if (is_array($mapping) && array_key_exists('column', $mapping)) {
            return $mapping['column'];
        }

        return null;
    }

    /**
     * Transform validated request values into model attributes using posts.map config.
     *
     * Supports direct mappings, date_now placeholders, and per-column options
     * such as max_characters and no-html.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mapValidatedData(Collection $columnsToMap, array $validated): array
    {
        $data = [];

        foreach ($columnsToMap as $columnToMapName => $columnToMap) {
            $column = null;
            $value = null;
            $options = [];

            if ($columnToMap === null) {
                continue;
            }

            if(is_callable($columnToMap)) {
                $data[$columnToMapName] = call_user_func($columnToMap);
            } elseif (is_array($columnToMap)) {
                if(array_key_exists('column', $columnToMap) && array_key_exists('options', $columnToMap)) {
                    $column = $columnToMap['column'];
                    $options = $columnToMap['options'];
                } elseif(array_key_exists('value', $columnToMap)) {
                    $value = $columnToMap['value'];
                }
            } else {
                $column = $columnToMap;
            }

            if ($column === 'date_now') {
                $data[$columnToMapName] = now()->toDateTimeString();
            } elseif($value) {
                $data[$columnToMapName] = $value;
            } elseif (array_key_exists($column, $validated)) {
                $data[$columnToMapName] = $validated[$column];
            }

            if (sizeof($options)) {
                foreach ($options as $option) {
                    [$optionName, $optionValue] = array_pad(explode(':', $option, 2), 2, null);

                    $data[$columnToMapName] = match ($optionName) {
                        'max_characters' => Str::limit($data[$columnToMapName], (int) $optionValue),
                        'no-html' => strip_tags($data[$columnToMapName]),
                    };
                }
            }
        }

        return $data;
    }

    /**
     * Process uploaded images through configured callbacks and merge results into $data.
     *
     * Each entry in posts.images may define a target column and a callable that
     * receives the uploaded file and column name.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attachImages(Model $model, array $validated, array $data): array
    {
        $imagesSection = config('posts.images');

        $image = array_key_exists('image', $validated)
            ? $validated['image'] : null;

        if ($imagesSection && is_array($imagesSection)) {
            foreach ($imagesSection as $imageSection) {
                $imageColumn = $imageSection['column'];
                $imageCallback = $imageSection['callback'];

                if ($imageColumn
                    && Schema::hasColumn($model->getTable(), $imageColumn)
                    && $imageCallback
                    && is_callable($imageCallback)
                    && $image)
                {
                    $data[$imageColumn] = call_user_func($imageCallback, $image, $imageColumn);
                }
            }
        }

        return $data;
    }
}
