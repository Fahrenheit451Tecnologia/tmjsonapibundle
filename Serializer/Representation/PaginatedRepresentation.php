<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Representation;

class PaginatedRepresentation
{
    /**
     * @var array
     */
    protected $items;

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $pages;

    /**
     * @var int
     */
    protected $total;

    /**
     * @param $items
     * @param $page
     * @param $limit
     * @param $pages
     * @param $total
     */
    public function __construct(
        $items,
        $page,
        $limit,
        $pages,
        $total
    ) {
        $this->items = $items;
        $this->page = $page;
        $this->limit = $limit;
        $this->pages = $pages;
        $this->total = $total;
    }

    /**
     * @return array|\Traversable
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->page < $this->getPages();
    }

    /**
     * @return int
     */
    public function getNextPage()
    {
        if ($this->hasNextPage()) {
            return $this->page + 1;
        }
    }

    /**
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->page > 1;
    }

    /**
     * @return int
     */
    public function getPreviousPage()
    {
        if ($this->hasPreviousPage()) {
            return $this->page - 1;
        }

        return null;
    }

    /**
     * Calculates the current page offset start
     *
     * @return int
     */
    public function getStart()
    {
        return $this->getTotal() ? (($this->getPage() - 1) * $this->getLimit()) + 1 : 0;
    }

    /**
     * Calculates the current page offset end
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->hasNextPage() ?
               $this->getPage() * $this->getLimit() :
               $this->getTotal();
    }

    /**
     * Get count
     *
     * @return int
     */
    public function getCount()
    {
        return max($this->getEnd() - $this->getStart(), 0);
    }
}
