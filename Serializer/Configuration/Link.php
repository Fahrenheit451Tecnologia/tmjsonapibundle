<?php declare(strict_types=1);

namespace TM\JsonApiBundle\Serializer\Configuration;

class Link
{
    const NAME_SELF     = 'self';
    const NAME_RELATED  = 'related';

    const AVAILABLE_NAMES = [
        self::NAME_RELATED,
        self::NAME_SELF,
    ];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var array|string[]
     */
    protected $routeParameters;

    /**
     * @var boolean
     */
    protected $absolute = true;

    /**
     * @param string       $name
     * @param string       $routeName
     * @param string|array $routeParameters
     * @param boolean      $absolute
     */
    public function __construct(
        string $name,
        string $routeName,
        array $routeParameters = [],
        bool $absolute = true
    ) {
        if (!in_array($name, self::AVAILABLE_NAMES)) {
            throw new \InvalidArgumentException(sprintf(
                'Links can only currently be named on of "%s", "%s" given',
                implode('", "', self::AVAILABLE_NAMES),
                $name
            ));
        }

        $this->name = $name;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
        $this->absolute = $absolute;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Get routeName
     *
     * @return string
     */
    public function getRouteName() : string
    {
        return $this->routeName;
    }

    /**
     * Get routeParameters
     *
     * @return array|string[]
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * Get absolute
     *
     * @return boolean
     */
    public function isAbsolute() : bool
    {
        return $this->absolute;
    }
}