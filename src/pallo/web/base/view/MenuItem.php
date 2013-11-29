<?php

namespace pallo\web\base\view;

/**
 * Data container for a menu item
 */
class MenuItem {

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
     * Construct a new menu item
     * @param string $label The label
     * @param string $translationKey The translation key for the label
     * @param array $translationParameters Parameters for the translation key
     * @param string $routeId The id of the route
     * @param array $routeParameters Parameters for the route
     * @return null
     */
    public function __construct() {
        $this->label = null;
        $this->translationKey = null;
        $this->translationParameters = null;
        $this->url = null;
        $this->routeId = null;
        $this->routeParameters = null;
    }

    /**
     * Gets a string representation of this item
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

}