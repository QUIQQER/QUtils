<?php

/**
 * This file contains the \QUI\Utils\Grid
 */

namespace QUI\Utils;

use QUI;

use function array_slice;
use function count;
use function is_array;

/**
 * Helper for the javascript controls/grid/Grid
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Grid extends QUI\QDOM
{
    /**
     * constructor
     *
     * @param array $params - optional
     */
    public function __construct(array $params = [])
    {
        // defaults
        $this->setAttribute('max', 50);
        $this->setAttribute('page', 1);

        $this->setAttributes($params);
    }

    /**
     * Prepares DB parameters with limits
     *
     * @param mixed $params
     *
     * @return array
     */
    public function parseDBParams(mixed $params = []): array
    {
        if (!is_array($params)) {
            return [];
        }

        $query = [];

        if (isset($params['perPage'])) {
            $this->setAttribute('max', $params['perPage']);
        }

        if (isset($params['page'])) {
            $this->setAttribute('page', $params['page']);
        }

        if ($this->getAttribute('page')) {
            $page = ($this->getAttribute('page') - 1);
            $start = $page * $this->getAttribute('max');

            $query['limit'] = $start . ',' . $this->getAttribute('max');
        }

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        if (!empty($params['sortOn'])) {
            $sortBy = '';
            $sortOn = $params['sortOn'];

            if (isset($params['sortBy'])) {
                $sortBy = $params['sortBy'];
            }

            if ($sortBy != 'DESC' && $sortBy != 'ASC') {
                $sortBy = '';
            }

            $query['order'] = $sortOn . ' ' . $sortBy;
        }

        return $query;
    }

    /**
     * Prepares the result for the Grid
     *
     * @param array $data
     * @param boolean|integer $count
     *
     * @return array
     */
    public function parseResult(array $data, bool|int $count = false): array
    {
        if ($count === false) {
            $count = count($data);
        }

        return [
            'data' => $data,
            'page' => $this->getAttribute('page'),
            'total' => $count
        ];
    }

    /**
     * Parse a result array in a grid array
     *
     * @param array $data
     * @param integer $page
     * @param integer $limit
     *
     * @return array
     */
    public static function getResult(array $data, int $page, int $limit): array
    {
        $count = count($data);
        $end = $page * $limit;
        $start = $end - $limit;

        return [
            'data' => array_slice($data, $start, $limit),
            'page' => $page,
            'total' => $count
        ];
    }
}
