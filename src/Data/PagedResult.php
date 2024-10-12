<?php declare(strict_types=1);

namespace Lalaz\Data;

/**
 * Class PagedResult
 *
 * This class represents a paginated result set, typically used for handling data that is split across multiple pages.
 * It contains information about the total number of records, the current page, the page size, and the records themselves.
 * It also provides helper methods to determine the total number of pages and whether previous or next pages are available.
 *
 * @package elasticmind\lalaz-framework
 * @author  Elasticmind <ola@elasticmind.io>
 * @link    https://lalaz.dev
 */
class PagedResult
{
    /** @var int $totalRecords The total number of records available */
    public int $totalRecords;

    /** @var int $currentPage The current page number */
    public int $currentPage;

    /** @var mixed $records The records for the current page */
    public mixed $records;

    /** @var int $pageSize The number of records per page */
    public int $pageSize;

    /**
     * Constructor for the PagedResult class.
     *
     * @param int   $totalRecords The total number of records available.
     * @param int   $pageSize     The number of records per page.
     * @param int   $currentPage  The current page number.
     * @param mixed $records      The records for the current page.
     */
    public function __construct(int $totalRecords, int $pageSize, int $currentPage, mixed $records)
    {
        $this->totalRecords = $totalRecords;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
        $this->records = $records;
    }

    /**
     * Calculates the total number of pages based on total records and page size.
     *
     * @return int The total number of pages.
     */
    public function totalPages(): int
    {
        return intval(ceil($this->totalRecords / $this->pageSize));
    }

    /**
     * Determines if there is a previous page available.
     *
     * @return bool True if a previous page exists, false otherwise.
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Determines if there is a next page available.
     *
     * @return bool True if a next page exists, false otherwise.
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages();
    }
}
