<?php

namespace Threls\ThrelsActivityLog;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Threls\ThrelsActivityLog\Actions\CreateActivityLogAction;
use Threls\ThrelsActivityLog\Contracts\ActivityLogContract;
use Threls\ThrelsActivityLog\Data\ActivityLogData;

class ActivityLogManager
{
    /** @var ActivityLogData[] */
    protected array $pendingLogs = [];

    protected bool $isAggregating = false;

    public function aggregate(Closure $callback): void
    {
        $this->isAggregating = true;
        $this->pendingLogs = [];

        try {
            $callback();
            $this->flush();
        } finally {
            $this->isAggregating = false;
            $this->pendingLogs = [];
        }
    }

    public function isAggregating(): bool
    {
        return $this->isAggregating;
    }

    public function addLog(ActivityLogData $log, Model $model): void
    {
        $this->pendingLogs[] = [
            'data' => $log,
            'model' => $model,
        ];
    }

    protected function flush(): void
    {
        if (empty($this->pendingLogs)) {
            return;
        }

        $rootLogs = [];
        $childLogs = [];

        foreach ($this->pendingLogs as $item) {
            $model = $item['model'];
            $parent = ($model instanceof ActivityLogContract) ? $model->getLogParent() : null;

            if ($parent) {
                $childLogs[] = $item;
            } else {
                $rootLogs[] = $item;
            }
        }

        // If no root logs were found but there are child logs, the first child's parent (if it exists)
        // or the child itself becomes the root. For simplicity, we assume a single logical root exists.
        if (empty($rootLogs) && ! empty($childLogs)) {
            $rootLogs[] = array_shift($childLogs);
        }

        foreach ($rootLogs as $rootItem) {
            $rootData = $rootItem['data'];
            $rootModel = $rootItem['model'];

            $this->attachChildren($rootData, $rootModel, $childLogs);

            app(CreateActivityLogAction::class)->execute($rootData);
        }
    }

    /**
     * @param array<int, array{data: ActivityLogData, model: Model}> $childLogs
     */
    protected function attachChildren(ActivityLogData $parentData, Model $parentModel, array &$childLogs): void
    {
        foreach ($childLogs as $key => $childItem) {
            $childModel = $childItem['model'];
            $childParent = ($childModel instanceof ActivityLogContract) ? $childModel->getLogParent() : null;

            if ($childParent && $childParent->is($parentModel)) {
                $childData = $childItem['data'];
                unset($childLogs[$key]);

                // Recursive call to handle deeper nesting
                $this->attachChildren($childData, $childModel, $childLogs);

                $relationName = $this->guessRelationName($parentModel, $childModel);

                $relations = $parentData->relations;
                $relations[$relationName][] = [
                    'model_id' => $childData->model_id,
                    'model_type' => $childData->model_type,
                    'type' => $childData->type,
                    'data' => [
                        'old' => $childData->data->old,
                        'new' => $childData->data->new,
                    ],
                    'dirty_keys' => $childData->dirty_keys,
                    'relations' => $childData->relations,
                ];
                $parentData->relations = $relations;
            }
        }
    }

    protected function guessRelationName(Model $parent, Model $child): string
    {
        // pluralized class name of the child
        return strtolower(Str::plural(class_basename($child)));
    }
}
