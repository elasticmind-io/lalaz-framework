<?php

use Lalaz\Data\PagedResult;

describe('PagedResultUnitTests', function() {
    it('should calculate total pages correctly', function () {
        // Arrange: Create a PagedResult with 100 total records and 10 records per page
        $pagedResult = new PagedResult(100, 10, 1, []);

        // Act: Calculate total pages
        $totalPages = $pagedResult->totalPages();

        // Assert: There should be 10 total pages
        expect($totalPages)->toBe(10);
    });

    it('should determine when previous page is available', function () {
        // Arrange: Create a PagedResult with 3 current page
        $pagedResult = new PagedResult(100, 10, 3, []);

        // Act: Check if previous page is available
        $hasPrevious = $pagedResult->hasPreviousPage();

        // Assert: Previous page should be available
        expect($hasPrevious)->toBeTrue();
    });

    it('should determine when previous page is not available', function () {
        // Arrange: Create a PagedResult with 1 current page
        $pagedResult = new PagedResult(100, 10, 1, []);

        // Act: Check if previous page is available
        $hasPrevious = $pagedResult->hasPreviousPage();

        // Assert: Previous page should not be available
        expect($hasPrevious)->toBeFalse();
    });

    it('should determine when next page is available', function () {
        // Arrange: Create a PagedResult with 3 current page and 10 total pages
        $pagedResult = new PagedResult(100, 10, 3, []);

        // Act: Check if next page is available
        $hasNext = $pagedResult->hasNextPage();

        // Assert: Next page should be available
        expect($hasNext)->toBeTrue();
    });

    it('should determine when next page is not available', function () {
        // Arrange: Create a PagedResult with the last page
        $pagedResult = new PagedResult(100, 10, 10, []);

        // Act: Check if next page is available
        $hasNext = $pagedResult->hasNextPage();

        // Assert: Next page should not be available
        expect($hasNext)->toBeFalse();
    });

    it('should return zero total pages when no records are available', function () {
        // Arrange: Create a PagedResult with zero total records
        $pagedResult = new PagedResult(0, 10, 1, []);

        // Act: Calculate total pages
        $totalPages = $pagedResult->totalPages();

        // Assert: There should be zero total pages
        expect($totalPages)->toBe(0);
    });
});
