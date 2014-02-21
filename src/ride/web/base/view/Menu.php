<?php

namespace ride\web\base\view;

use ride\library\i18n\translator\Translator;
use ride\library\router\RouteContainer;
use ride\library\security\SecurityManager;

/**
 * Data container for a menu
 */
class Menu {

    /**
     * Value for a separator
     * @var string
     */
    const SEPARATOR = '-';

    /**
     * Display label
     * @var string
     */
    private $label;

    /**
     * Translation key for the label
     * @var string
     */
    private $translationKey;

    /**
     * Parameters for the translation key
     * @var array
     */
    private $translationParameters;

    /**
     * Array with the sub menu items
     * @var array
     */
    private $items;

    /**
     * Construct this menu
     * @return null
     */
    public function __construct() {
        $this->label = null;
        $this->translationKey = null;
        $this->translationParameters = null;
        $this->items = array();
    }

    /**
     * Gets a string representation of this menu
     * @return string
     */
    public function __toString() {
        if ($this->label) {
            return $this->label;
        } elseif ($this->translationKey) {
            return $this->translationKey;
        } else {
            return 'MenuItem';
        }
    }

    /**
     * Set the label of this menu
     * @param string $label
     * @return null
     */
    public function setLabel($label) {
        $this->label = $label;
    }

    /**
     * Get the label of this menu
     * @return string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * Sets the translation
     * @param string $key The key of the translation
     * @param array $parameters The parameters for the route
     * @return null
     */
    public function setTranslation($key, array $parameters = null) {
        $this->translationKey = $key;
        $this->translationParameters = $parameters;
    }

    /**
     * Get the translation key
     * @return string
     */
    public function getTranslationKey() {
       return $this->translationKey;
    }

    /**
     * Gets the parameters for the translation key
     * @return array
     */
    public function getTranslationParameters() {
        return $this->translationParameters;
    }

    /**
     * Add a sub menu item to this menu
     * @param MenuItem $menuItem
     * @return null
     */
    public function addMenuItem(MenuItem $menuItem) {
        $this->items[] = $menuItem;
    }

    /**
     * Add a sub menu to this menu
     * @param Menu $menu
     * @return null
     */
    public function addMenu(Menu $menu) {
        $this->items[] = $menu;
    }

    /**
     * Add a separator to this menu
     * @return null
     */
    public function addSeparator() {
        $this->items[] = self::SEPARATOR;
    }

    /**
     * Adds a heading to this menu
     * @param string $header
     * @return null
     */
    public function addHeader($header) {
        $this->items[] = $header;
    }

    /**
     * Check whether this menu contains sub items
     * @return boolean true if there are items in this menu, false otherwise
     */
    public function hasItems() {
        foreach ($this->items as $item) {
            if (is_string($item)) {
                continue;
            }

            if (is_string($item) || $item instanceof MenuItem) {
                return true;
            }

            if ($item->hasItems()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes a item from this menu
     * @param mixed $item The item to remove
     * @return boolean True when the item has been found and removed, false
     * otherwise
     */
    public function removeItem($item) {
        if ($item === self::SEPARATOR) {
            return false;
        }

        foreach ($this->items as $index => $i) {
            if ($i === $item) {
                unset($this->items[$index]);

                return true;
            }
        }

        return false;
    }

    /**
     * Removes all the items from this menu
     * @return null
     */
    public function removeAll() {
        $this->items = array();
    }

    /**
     * Get the sub item for a label
     * @param string $label String representation of the item
     * @return Menu|MenuItem|null the item with the provided string
     * representation when found, null otherwise
     */
    public function getItem($label) {
        foreach ($this->items as $item) {
            if ($item === self::SEPARATOR) {
                continue;
            }

            if ((string) $item == $label) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Get all the sub items of this menu
     * @return array Array with all the sub items of this menu (Menu, MenuItem or a '-')
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * Processes the menu items of this menu.
     *
     * <p>The labels of the menu items will be translated if applicable. The
     * routes will be translated into URLs. All menu items for which the
     * current user has no permission will be filtered out.</p>
     * @param ride\library\i18n\translator\Translator $translator Translator
     * to translate the labels
     * @param ride\library\security\SecurityManager $securityManager Security
     * manager to check the permissions of the routes
     * @param ride\library\router\RouteContainer $routeContainer Container of
     * the available routes
     * @param string $baseUrl To create routes from the URLs
     * @param ride\library\security\SecurityManager $securityManager Instance
     * of the security manager
     * @return null
     */
    public function process(Translator $translator, RouteContainer $routeContainer, $baseUrl, SecurityManager $securityManager = null) {
        if (!$this->label && $this->translationKey) {
            $this->setLabel($translator->translate($this->translationKey, $this->translationParameters));
        }

        foreach ($this->items as $index => $item) {
            if (is_string($item)) {
                continue;
            }

            $label = $item->getLabel();
            $translationKey = $item->getTranslationKey();
            if (!$label && $translationKey) {
                $item->setLabel($translator->translate($translationKey, $item->getTranslationParameters()));
            }

            if ($item instanceof self) {
                $item->process($translator, $routeContainer, $baseUrl, $securityManager);

                if (!$item->hasItems()) {
                    $this->removeItem($item);
                }

                continue;
            }

            $url = $item->getUrl();
            $routeId = $item->getRouteId();

            if (!$url && $routeId) {
                $url = $routeContainer->getUrl($baseUrl, $routeId, $item->getRouteParameters());

                $item->setUrl($url);
            }

            if (!$securityManager || strpos($url, $baseUrl) !== 0) {
                continue;
            }

            $path = str_replace($baseUrl, '', $url);
            if (!$securityManager->isPathAllowed($path)) {
                $this->removeItem($item);
            }
        }
    }

    /**
     * Order the items in this menu alphabetically. Separator will be removed by calling this method.
     * @param boolean $recursive true to order the items recursivly, false to only order 1 level
     * @return null
     */
    public function orderItems($recursive = true) {
        $orderIndex = 0;
        $orderArrays = array(
            0 => array()
        );

        foreach ($this->items as $index => $item) {
            if ($item === self::SEPARATOR) {
                $orderIndex++;
                $orderArrays[$orderIndex] = array();

                continue;
            }

            $orderArrays[$orderIndex][] = $item;

            if ($recursive && $item instanceof self) {
                $item->orderItems(true);
            }
        }

        $this->items = array();

        foreach ($orderArrays as $orderIndex => $orderArray) {
            usort($orderArray, array($this, 'compareItems'));

            if ($this->items) {
                $this->items[] = self::SEPARATOR;
            }

            foreach ($orderArray as $item) {
                $this->items[] = $item;
            }
        }
    }

    /**
     * Compare 2 items of a menu
     * @param Menu|MenuItem $a
     * @param Menu|MenuItem $b
     * @return 0 when $a and $b are the same, 1 when $a is bigger then $b, -1 otherwise
     */
    public static function compareItems($a, $b) {
        $al = strtolower($a->getLabel());
        $bl = strtolower($b->getLabel());

        if ($al == $bl) {
            return 0;
        }
        return ($al > $bl) ? +1 : -1;
    }

}