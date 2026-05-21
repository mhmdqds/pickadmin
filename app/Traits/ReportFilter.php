<?php

namespace App\Traits;



trait ReportFilter
{
    public static function scopeApplyDateFilter($query, $filter, $from = null, $to = null, $column = 'created_at')
    {
        return $query->when(isset($from) && isset($to)  && $filter == 'custom', function ($query) use ($from, $to, $column) {
            return $query->whereBetween($column, [$from . " 00:00:00", $to . " 23:59:59"]);
        })
            ->when($filter == 'this_year', function ($query) use ($column) {
                return $query->whereYear($column, now()->format('Y'));
            })
            ->when($filter == 'this_month', function ($query) use ($column) {
                return $query->whereMonth($column, now()->format('m'))->whereYear($column, now()->format('Y'));
            })
            ->when($filter == 'previous_year', function ($query) use ($column) {
                return $query->whereYear($column, date('Y') - 1);
            })
            ->when($filter == 'this_week', function ($query) use ($column) {
                return $query->whereBetween($column, [now()->startOfWeek()->format('Y-m-d H:i:s'), now()->endOfWeek()->format('Y-m-d H:i:s')]);
            });
        return $query;
    }

    public static function scopeApplyRelationShipSearch($query, $relationships,$searchParameter )
    {
        foreach ($relationships as $relation => $field) {
            $query->orWhereHas($relation, function ($query) use ($field, $searchParameter) {
                $query->where(function ($q) use ($field, $searchParameter) {
                    foreach ($searchParameter as $value) {
                        $q->orWhere($field, 'like', "%{$value}%");
                    }
                });
            });
        }

        return $query;
    }

 public function scopeSearch($query, $keywords, $relations = [], $mainCol = 'name')
    {
        if (empty($keywords)) {
            return $query;
        }
        $keywords = is_array($keywords) ? $keywords : explode(' ', $keywords);
        $keywords = array_filter(array_map('trim', $keywords));

        if (empty($keywords)) {
            return $query;
        }

        $fullText = implode(' ', $keywords);
        $mainColumns = is_array($mainCol) ? $mainCol : [$mainCol];
        $defaultColumn = $mainColumns[0];

        $this->validateColumnName($defaultColumn);
        $query->where(function ($q) use ($keywords, $relations, $mainColumns) {
            // Search in main columns (ALL keywords must match at least one column)
            foreach ($keywords as $word) {
                $q->where(function ($subQ) use ($word, $mainColumns) {
                    foreach ($mainColumns as $column) {
                        $subQ->orWhere($column, 'like', "%{$word}%");
                    }
                });
            }

            // Search in relationships
            foreach ($relations as $relation => $column) {
                $q->orWhereHas($relation, function ($rq) use ($column, $keywords) {
                    foreach ($keywords as $word) {
                        $rq->where($column, 'like', "%{$word}%");
                    }
                });
            }
        });
        return $query->orderByRaw(
            "CASE
            WHEN `{$defaultColumn}` = ? THEN 1
            WHEN `{$defaultColumn}` LIKE ? THEN 2
            WHEN `{$defaultColumn}` LIKE ? THEN 3
            ELSE 4
        END, LENGTH(`{$defaultColumn}`) ASC, `{$defaultColumn}` ASC",
            [$fullText, "{$fullText}%", "%{$fullText}%"]
        );
    }

    protected function validateColumnName($column)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new \InvalidArgumentException("Invalid column name: {$column}");
        }
    }



}
