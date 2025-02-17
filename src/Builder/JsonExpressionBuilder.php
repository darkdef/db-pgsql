<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use JsonException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Json\Json;

/**
 * Builds expressions for {@see `Yiisoft\Db\Expression\JsonExpression`} for PostgreSQL Server.
 */
final class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     *
     * @psalm-param JsonExpression $expression
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /**
         * @psalm-var array|mixed|QueryInterface $value
         */
        $value = $expression->getValue();

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return "($sql)" . $this->getTypecast($expression);
        }

        if ($value instanceof ArrayExpression) {
            $placeholder = 'array_to_json(' . $this->queryBuilder->buildExpression($value, $params) . ')';
        } else {
            $placeholder = $this->queryBuilder->bindParam(Json::encode($value), $params);
        }

        return $placeholder . $this->getTypecast($expression);
    }

    /**
     * @return string The typecast expression based on {@see JsonExpression::getType()}.
     */
    protected function getTypecast(JsonExpression $expression): string
    {
        $type = $expression->getType();

        return match ($type !== null) {
            true => '::' . $type,
            default => '',
        };
    }
}
