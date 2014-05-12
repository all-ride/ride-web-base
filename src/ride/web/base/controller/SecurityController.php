<?php

namespace ride\web\base\controller;

use ride\library\html\table\decorator\DataDecorator;
use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\TableHelper;
use ride\library\http\Header;
use ride\library\http\Response;
use ride\library\image\ImageUrlGenerator;
use ride\library\reflection\ReflectionHelper;
use ride\library\security\exception\EmailExistsException;
use ride\library\security\exception\UsernameExistsException;
use ride\library\system\file\browser\FileBrowser;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use ride\web\base\table\RoleTable;
use ride\web\base\table\UserTable;

/**
 * Controller to manage the security model
 */
class SecurityController extends AbstractController {

    /**
     * Gets the security model and skips the action of no security model set
     * @return boolean
     */
    public function preAction() {
        $this->securityModel = $this->getSecurityModel();
        if (!$this->securityModel) {
            return false;
        }

        return true;
    }

    /**
     * Gets the security model
     * @return \ride\library\security\model\SecurityModel|null
     */
    private function getSecurityModel() {
        $securityManager = $this->getSecurityManager();
        $securityModel = $securityManager->getSecurityModel();
        if ($securityModel) {
            return $securityModel;
        }

        $this->response->setStatusCode(Response::STATUS_CODE_SERVICE_UNAVAILABLE);

        return null;
    }

    /**
     * Action to get an overview of the users
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     * @param \ride\library\image\ImageUrlGenerator $imageUrlGenerator
     * @return null
     */
    public function usersAction(ReflectionHelper $reflectionHelper, ImageUrlGenerator $imageUrlGenerator) {
        $detailAction = $this->getUrl('system.security.user.edit', array('id' => '%id%'));
        $detailAction .= '?referer=' . urlencode($this->request->getUrl());

        $detailDecorator = new DataDecorator($reflectionHelper, $detailAction, $imageUrlGenerator);
        $detailDecorator->mapProperty('title', 'displayName');
        $detailDecorator->mapProperty('teaser', 'userName');
        $detailDecorator->mapProperty('image', 'image');

        $translator = $this->getTranslator();

        $table = new UserTable($this->securityModel, $reflectionHelper);
        $table->addDecorator($detailDecorator);
        $table->addDecorator(new ValueDecorator('email', null, $reflectionHelper));
        $table->addDecorator(new ValueDecorator('roles', null, $reflectionHelper));
        $table->setPaginationOptions(array(5, 10, 25, 50, 100, 250, 500));
        $table->addAction(
            $translator->translate('button.delete'),
            array($this, 'deleteUsers'),
            $translator->translate('label.table.confirm.delete')
        );

        $baseUrl = $this->getUrl('system.security.user');
        $rowsPerPage = 10;

        $form = $this->processTable($table, $baseUrl, $rowsPerPage);
        if ($this->response->willRedirect() || $this->response->getView()) {
            return;
        }

        $this->setTemplateView('base/users', array(
            'form' => $form->getView(),
            'table' => $table,
        ));
    }

    /**
     * Action to delete the data from the model
     * @param array $data Array of primary keys
     * @return null
     */
    public function deleteUsers($data) {
        if (!$data) {
            return;
        }

        $referer = $this->request->getHeader(Header::HEADER_REFERER);
        if (!$referer) {
            $referer = $this->request->getUrl();
        }

        $this->response->setRedirect($referer);

        foreach ($data as $id) {
            $user = $this->securityModel->getUserById($id);
            if (!$user) {
                continue;
            }

            $this->securityModel->deleteUser($user);

            $this->addSuccess('success.data.deleted', array('data' => $user->getDisplayName()));
        }
    }

    /**
     * Action to add or edit a user
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param string $id If of the role to edit
     * @return null
     */
    public function userFormAction(FileBrowser $fileBrowser, $id = null) {
        if ($id) {
            $user = $this->securityModel->getUserById($id);
            if (!$user) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }

            $data = array(
                'username' => $user->getUserName(),
                'name' => $user->getDisplayName(),
                'email' => $user->getEmail(),
                'image' => $user->getImage(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
            );
        } else {
            $user = $this->securityModel->createUser();
            $data = array();
        }

        $referer = $this->request->getQueryParameter('referer');
        $translator = $this->getTranslator();
        $roles = $this->securityModel->getRoles();
        $roleOptions = array();

        foreach ($roles as $role) {
            $roleOptions[$role->getId()] = $role->getName();
        }

        $form = $this->createFormBuilder($data);
        $form->setId('form-user');
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('password', 'password', array(
            'label' => $translator->translate('label.password'),
        ));
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name.profile'),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->addRow('email', 'email', array(
            'label' => $translator->translate('label.email'),
            'filters' => array(
                'trim' => array(),
            ),
        ));
        $form->addRow('image', 'image', array(
            'label' => $translator->translate('label.image'),
            'path' => $fileBrowser->getApplicationDirectory()->getChild('data/upload/profile')->getAbsolutePath(),
        ));
        $form->addRow('roles', 'option', array(
            'label' => $translator->translate('label.roles'),
            'multiple' => true,
            'options' => $roleOptions,
        ));
        $form->addRow('active', 'option', array(
            'label' => $translator->translate('label.active'),
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $user->setUserName($data['username']);
                $user->setDisplayName($data['name']);
                $user->setEmail($data['email']);
                $user->setImage($data['image']);
                $user->setIsActive($data['active']);

                if ($data['password']) {
                    $user->setPassword($data['password']);
                }

                try {
                    $this->securityModel->saveUser($user);
                } catch (UsernameExistsException $exception) {
                    $error = new ValidationError('error.validation.username.exists', 'Username already exists');

                    $validationException = new ValidationException();
                    $validationException->addError('username', $error);

                    throw $validationException;
                } catch (EmailExistsException $exception) {
                    $error = new ValidationError('error.validation.email.exists', 'Email address already exists');

                    $validationException = new ValidationException();
                    $validationException->addError('email', $error);

                    throw $validationException;
                }

                if (isset($data['roles'])) {
                    foreach ($data['roles'] as $dataRole) {
                        foreach ($roles as $role) {
                            if ($dataRole == $role->getId()) {
                                $data['roles'][$dataRole] = $role;
                            }
                        }
                    }
                } else {
                    $data['roles'] = array();
                }

                $this->securityModel->setRolesToUser($user, $data['roles']);

                $this->addSuccess('success.data.saved', array('data' => $user->getDisplayName()));

                if (!$referer) {
                    $referer = $this->getUrl('system.security.user');
                }
                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/user.form', array(
            'form' => $form->getView(),
            'user' => $user,
            'referer' => $referer,
        ));
    }

    /**
     * Action to get an overview of the roles
     * @return null
     */
    public function rolesAction(ReflectionHelper $reflectionHelper) {
        $translator = $this->getTranslator();

        $detailAction = $this->getUrl('system.security.role.edit', array('id' => '%id%'));
        $detailAction .= '?referer=' . urlencode($this->request->getUrl());

        $table = new RoleTable($this->securityModel, $reflectionHelper);
        $table->addDecorator(new DataDecorator($reflectionHelper, $detailAction));
        $table->setPaginationOptions(array(5, 10, 25, 50, 100, 250, 500));
        $table->addAction(
            $translator->translate('button.delete'),
            array($this, 'deleteRoles'),
            $translator->translate('label.table.confirm.delete')
        );

        $baseUrl = $this->getUrl('system.security.role');
        $rowsPerPage = 10;

        $form = $this->processTable($table, $baseUrl, $rowsPerPage);
        if ($this->response->willRedirect() || $this->response->getView()) {
            return;
        }

        $this->setTemplateView('base/roles', array(
            'form' => $form->getView(),
            'table' => $table,
        ));
    }

    /**
     * Action to delete the data from the model
     * @param array $data Array of primary keys
     * @return null
     */
    public function deleteRoles($data) {
        if (!$data) {
            return;
        }

        $referer = $this->request->getHeader(Header::HEADER_REFERER);
        if (!$referer) {
            $referer = $this->request->getUrl();
        }

        $this->response->setRedirect($referer);

        foreach ($data as $id) {
            $role = $this->securityModel->getRoleById($id);
            if(!$role) {
                continue;
            }

            $this->securityModel->deleteRole($role);

            $this->addSuccess('success.data.deleted', array('data' => $role->getName()));
        }
    }

    /**
     * Action to add or edit a role
     * @param string $id If of the role to edit
     * @return null
     */
    public function roleFormAction($id = null) {
        if ($id) {
            $role = $this->securityModel->getRoleById($id);
            if (!$role) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }

            $data = array(
                'name' => $role->getName(),
                'allowed-paths' => $this->getPathsString($role->getPaths()),
                'allowed-permissions' => $role->getPermissions(),
            );
        } else {
            $role = $this->securityModel->createRole();
            $data = array();
        }

        $referer = $this->request->getQueryParameter('referer');
        $translator = $this->getTranslator();
        $permissions = $this->securityModel->getPermissions();

        $form = $this->createFormBuilder($data);
        $form->setId('form-role');
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('allowed-paths', 'text', array(
            'label' => $translator->translate('label.paths.allowed'),
            'attributes' => array(
                'rows' => 5,
            ),
            'filters' => array(
                'trim' => array(
                    'trim.empty' => true,
                    'trim.lines' => true,
                ),
            )
        ));
        if ($permissions) {
            $form->addRow('granted-permissions', 'option', array(
                'label' => $translator->translate('label.permissions.granted'),
                'multiple' => true,
                'options' => $permissions,
            ));
        }

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();
                if ($data['allowed-paths']) {
                    $data['allowed-paths'] = explode("\n", str_replace("\r", "", $data['allowed-paths']));
                } else {
                    $data['allowed-paths'] = array();
                }

                $role->setName($data['name']);
                $this->securityModel->saveRole($role);
                $this->securityModel->setAllowedPathsToRole($role, $data['allowed-paths']);
                if ($permissions) {
                    $this->securityModel->setGrantedPermissionsToRole($role, $data['allowed-permissions']);
                }

                $this->addSuccess('success.data.saved', array('data' => $role->getName()));

                if (!$referer) {
                    $referer = $this->getUrl('system.security.role');
                }
                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/role.form', array(
            'form' => $form->getView(),
            'role' => $role,
            'referer' => $referer,
        ));
    }

    /**
     * Action to manage the secured paths
     * @return null
     */
    public function pathAction() {
        $translator = $this->getTranslator();

        $roles = $this->securityModel->getRoles();
        $securedPaths = $this->securityModel->getSecuredPaths();

        $data = array(
            'allowed-paths' => array(),
            'secured-paths' => $this->getPathsString($securedPaths),
        );

        foreach ($roles as $role) {
            $data['allowed-paths'][$role->getId()] = $this->getPathsString($role->getPaths());
        }

        $form = $this->createFormBuilder($data);
        if ($data['allowed-paths']) {
            $form->addRow('allowed-paths', 'text', array(
                'label' => $translator->translate('label.paths.allowed'),
                'description' => $translator->translate('label.paths.allowed.description'),
                'multiple' => true,
                'attributes' => array(
                    'rows' => 5,
                ),
                'filters' => array(
                    'trim' => array(
                        'trim.empty' => true,
                        'trim.lines' => true,
                    ),
                )
            ));
        }
        $form->addRow('secured-paths', 'text', array(
            'label' => $translator->translate('label.paths.secured'),
            'description' => $translator->translate('label.paths.secured.description'),
            'attributes' => array(
                'rows' => 5,
            ),
            'filters' => array(
                'trim' => array(
                    'trim.empty' => true,
                    'trim.lines' => true,
                ),
            ),
        ));


        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                foreach ($roles as $role) {
                    $roleId = $role->getId();
                    if (isset($data['allowed-paths'][$roleId])) {
                        $paths = explode("\n", str_replace("\r", "", $data['allowed-paths'][$roleId]));
                    } else {
                        $paths = array();
                    }

                    $this->securityModel->setAllowedPathsToRole($role, $paths);
                }

                if ($data['secured-paths']) {
                    $paths = explode("\n", str_replace("\r", "", $data['secured-paths']));

                    $this->securityModel->setSecuredPaths($paths);
                } else {
                    $this->securityModel->setSecuredPaths(array());
                }

                $this->addSuccess('success.security.saved');

                $this->response->setRedirect($this->getUrl('system.security'));

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/security', array(
            'form' => $form->getView(),
            'roles' => $roles,
        ));
    }

    /**
     * Gets a path string for an array of routes
     * @param array $paths Array with a path as value
     * @return string
     */
    private function getPathsString(array $paths) {
        sort($paths);

        $string = '';
        foreach ($paths as $path) {
            if (empty($path)) {
                continue;
            }

            $string .= ($string ? "\n" : '') . $path;
        }

        return $string;
    }

}
