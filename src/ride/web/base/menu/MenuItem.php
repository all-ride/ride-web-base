<?php

namespace ride\web\base\menu;

/**
 * Data container for a menu item
 */
class MenuItem {

    /**
     * Internal id of this menu
     */
    private $id;

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
     * The link URL
     * @var string
     */
    private $url;

    /**
     * Route for the URL
     * @var string
     */
    private $route;

    /**
     * Parameters for the route
     * @var array
     */
    private $routeParameters;

    /**
     * Weight to order inside a menu
     * @var integer
     */
    private $weight;

    private $routeId;

    /**
     * Construct a new menu item
     * @return null
     */
    public function __construct() {
        $this->id = null;
        $this->label = null;
        $this->translationKey = null;
        $this->translationParameters = null;
        $this->url = null;
        $this->routeId = null;
        $this->routeParameters = null;
        $this->weight = 0;
    }

    /**
     * Gets a string representation of this item
     * @return string
     */
    public function __toString() {
        if ($this->id) {
            return $this->id;
        } elseif ($this->label) {
            return $this->label;
        } elseif ($this->translationKey) {
            return $this->translationKey;
        } else {
            return 'MenuItem';
        }
    }

    /**
     * Sets the id of this menu item
     * @param string $id Id of this menu item
     * @return null
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Gets the id of this menu item
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the label of this menu item
     * @param string $label Translation key for the label
     * @return null
     */
    public function setLabel($label) {
        $this->label = $label;
    }

    /**
     * Gets the label of this menu item
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
     * Get the id of the route
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
     * Sets the URL of this menu item
     * @param string $urk The link URL
     * @return null
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * Gets the URL of this menu item
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Sets the route
     * @param string $id The id of the route
     * @param array $parameters The parameters for the route
     * @return null
     */
    public function setRoute($id, array $parameters = null) {
        $this->routeId = $id;
        $this->routeParameters = $parameters;
    }

    /**
     * Get the id of the route
     * @return string
     */
    public function getRouteId() {
        return $this->routeId;
    }

    /**
     * Gets the parameters for the route
     * @return array
     */
    public function getRouteParameters() {
        return $this->routeParameters;
    }

    /**
     * Sets the weight of this menu item
     * @param integer $weight Value to compare with other menu items when
     * ordering, weight has more priority then label
     * @return null
     */
    public function setWeight($weight) {
        $this->weight = $weight;
    }

    /**
     * Gets the weight of this menu item
     * @return integer
     */
    public function getWeight() {
        return $this->weight;
    }

}
