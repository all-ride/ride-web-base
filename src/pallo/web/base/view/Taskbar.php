<?php

namespace pallo\web\base\view;

/**
 * Taskbar for quick access to the different parts of your application
 */
class Taskbar {

    /**
     * Event to process the taskbar
     * @var string
     */
    const EVENT_TASKBAR = 'app.taskbar';

    /**
     * Title of the taskbar
     * @var string
     */
    protected $title;

    /**
     * The applications menu
     * @var Menu
     */
    protected $applicationsMenu;

    /**
     * The settings menu
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
     * Get the title of the taskbar
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Get the applications menu
     * @return Menu
     */
    public function getApplicationsMenu() {
        return $this->applicationsMenu;
    }

    /**
     * Get the settings menu
     * @return Menu
     */
    public function getSettingsMenu() {
        return $this->settingsMenu;
    }

}