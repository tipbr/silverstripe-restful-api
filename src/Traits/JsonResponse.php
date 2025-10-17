<?php

namespace TipBr\RestfulApi\Traits;

use ArrayAccess;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Model\List\PaginatedList;
use SilverStripe\Model\List\SS_List;

trait JsonResponse
{
    /**
     * Outputs a successful response (200) with standardized format
     *
     * @param array $context
     * @return HTTPResponse
     */
    protected function success(array $context = []): HTTPResponse
    {
        $this->getResponse()->setBody(json_encode([
            'success' => true,
            'timestamp' => time(),
            'data' => $context
        ]));

        return $this->getResponse();
    }

    /**
     * Returns a standardized error response
     *
     * @param array $context
     * @return HTTPResponse
     */
    protected function failure(array $context = []): HTTPResponse
    {
        $response = $this->getResponse();

        $statusCode = $context['status_code'] ?? 400;
        $message = $context['message'] ?? $context['error'] ?? 'An error occurred';

        $body = [
            'success' => false,
            'timestamp' => time(),
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ]
        ];

        if (isset($context['details'])) {
            $body['error']['details'] = $context['details'];
        }

        $response->setBody(json_encode($body));
        $response->setStatusCode($statusCode);

        return $response;
    }

    /**
     * Returns a HTTP response with the provided data encoded as JSON
     *
     * @param array $data
     * @return HTTPResponse
     */
    protected function returnArray(array $data): HTTPResponse
    {
        return $this->getResponse()->setBody(json_encode($data));
    }

    /**
     * Returns a JSON response with pagination metadata
     *
     * @param ArrayAccess $list
     * @param callable|null $keyFunc
     * @param callable|null $dataFunc
     * @param int|null $pageLength
     * @return HTTPResponse
     */
    protected function returnPaginated(
        ArrayAccess $list,
        ?callable $keyFunc = null,
        ?callable $dataFunc = null,
        ?int $pageLength = 100
    ): HTTPResponse {
        list($list, $output) = $this->prepPaginatedOutput($list, $keyFunc, $dataFunc, $pageLength);

        return $this->returnArray([
            'records' => $output,
            'start' => $list->getPageStart(),
            'limit' => $list->getPageLength(),
            'total' => $list->getTotalItems(),
            'more' => ($list->NextLink()) ? true : false
        ]);
    }

    /**
     * Convert a provided DataList and return formatted output
     *
     * @param SS_List $list
     * @param callable|null $keyFunc
     * @param callable|null $dataFunc
     * @return array
     */
    protected function prepList(SS_List $list, ?callable $keyFunc = null, ?callable $dataFunc = null): array
    {
        $output = [];

        foreach ($list as $item) {
            if ($dataFunc) {
                $record = $dataFunc($item);
            } elseif (is_array($item)) {
                $record = $item;
            } else {
                $record = $item->toApi();
            }

            if ($keyFunc) {
                $output[$keyFunc($item)] = $record;
            } else {
                $output[] = $record;
            }
        }

        return [
            $list,
            $output
        ];
    }

    /**
     * Convert a provided List to a PaginatedList and return formatted output
     *
     * @param SS_List $list
     * @param callable|null $keyFunc
     * @param callable|null $dataFunc
     * @param int|null $pageLength
     * @return array
     */
    protected function prepPaginatedOutput(
        SS_List $list,
        ?callable $keyFunc = null,
        ?callable $dataFunc = null,
        ?int $pageLength = null
    ): array {
        $list = PaginatedList::create($list, $this->request);

        if ($pageLength) {
            $list->setPageLength($pageLength);
        }

        $output = [];

        foreach ($list as $item) {
            if ($dataFunc) {
                $record = $dataFunc($item);
            } elseif (is_array($item)) {
                $record = $item;
            } else {
                $record = $item->toApi();
            }

            if ($keyFunc) {
                $output[$keyFunc($item)] = $record;
            } else {
                $output[] = $record;
            }
        }

        return [
            $list,
            $output
        ];
    }

    /**
     * Returns a JSON response
     *
     * @param mixed $value
     * @return HTTPResponse
     */
    protected function returnJSON(mixed $value): HTTPResponse
    {
        return $this->getResponse()->setBody(json_encode($value));
    }
}
