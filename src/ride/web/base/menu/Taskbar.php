<?php

namespace ride\web\base\menu;

/**
 * Taskbar for quick access to the different parts of your application
 */
class Taskbar {

    /**
     * Title of the taskbar
     * @var string
     */
    protected $title;

    /**
     * Applications menu
     * @var Menu
     */
    protected $applicationsMenu;

    /**
     * Settings menu
     * @var Menu
     */
    protected $settingsMenu;

    /**
     * Construct the taskbar
     * @return null
     */
    public function __construct() {
        $this->title = null;
        $this->applicationsMenu = new Menu();
        $this->settingsMenu = new Menu();
    }

    /**
     * Set the title of the taskbar
     * @param string $title
     * @return null
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Gets the title of the taskbar
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the applications menu
     * @param Menu $applicationsMenu
     */
    public function setApplicationsMenu(Menu $applicationsMenu) {
        $this->applicationsMenu = $applicationsMenu;
    }

    /**
     * Gets the applications menu
     * @return Menu
     */
    public function getApplicationsMenu() {
        return $this->applicationsMenu;
    }

    /**
     * Sets the settings menu
     * @param Menu $settingsMenu
     */
    public function setSettingsMenu(Menu $settingsMenu) {
        $this->settingsMenu = $settingsMenu;
    }

    /**
     * Gets the settings menu
     * @return Menu
     */
    public function getSettingsMenu() {
        return $this->settingsMenu;
    }

}
