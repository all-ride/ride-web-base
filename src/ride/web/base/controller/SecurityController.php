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
use ride\library\security\exception\UnauthorizedException;
use ride\library\security\exception\UsernameExistsException;
use ride\library\system\file\browser\FileBrowser;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use ride\web\base\table\decorator\UserLockDecorator;
use ride\web\base\table\PermissionTable;
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
        $userWeight = $this->getUserWeight();

        $detailAction = $this->getUrl('system.security.user.edit', array('id' => '%id%'));
        $detailAction .= '?referer=' . urlencode($this->request->getUrl());

        $detailDecorator = new DataDecorator($reflectionHelper, $detailAction, $imageUrlGenerator, $this->getTheme() . '/img/data.png');
        $detailDecorator->mapProperty('title', 'displayName');
        $detailDecorator->mapProperty('teaser', 'userName');
        $detailDecorator->mapProperty('image', 'image');

        $translator = $this->getTranslator();

        $table = new UserTable($this->securityModel, $reflectionHelper);
        $table->addDecorator($detailDecorator);
        $table->addDecorator(new ValueDecorator('email', null, $reflectionHelper));
        $table->addDecorator(new ValueDecorator('roles', null, $reflectionHelper));
        $table->addDecorator(new UserLockDecorator($userWeight, $translator->translate('label.locked'), 'disabled'));
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

        $userWeight = $this->getUserWeight();

        foreach ($data as $id) {
            $user = $this->securityModel->getUserById($id);
            if (!$user || $user->getRoleWeight() > $userWeight) {
                continue;
            }

            $this->securityModel->deleteUser($user);

            $this->addSuccess('success.data.deleted', array('data' => $user->getDisplayName()));
        }

        $referer = $this->request->getHeader(Header::HEADER_REFERER);
        if (!$referer) {
            $referer = $this->request->getUrl();
        }

        $this->response->setRedirect($referer);
    }

    /**
     * Action to add or edit a user
     * @param \ride\library\system\file\browser\FileBrowser $fileBrowser
     * @param string $id If of the role to edit
     * @return null
     */
    public function userFormAction(FileBrowser $fileBrowser, $id = null) {
        $userWeight = $this->getUserWeight();

        if ($id) {
            $user = $this->securityModel->getUserById($id);
            if (!$user) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            } elseif ($user->getRoleWeight() > $userWeight) {
                throw new UnauthorizedException();
            }

            $data = array(
                'username' => $user->getUserName(),
                'name' => $user->getDisplayName(),
                'email' => $user->getEmail(),
                'image' => $user->getImage(),
                'roles' => $user->getRoles(),
                'email-confirmed' => $user->isEmailConfirmed(),
                'active' => $user->isActive(),
            );

            $passwordValidators = array();
        } else {
            $user = $this->securityModel->createUser();
            $data = array();
            $passwordValidators = array('required' => array());
        }

        $referer = $this->request->getQueryParameter('referer');
        $translator = $this->getTranslator();
        $roles = $this->securityModel->getRoles();
        $roleOptions = array();

        foreach ($roles as $role) {
            if ($userWeight != -1 && $role->getWeight() > $userWeight) {
                continue;
            }

            $roleOptions[$role->getId()] = $role->getName();
        }
        asort($roleOptions);

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
            'attributes' => array(
                'autocomplete' => 'off',
            ),
            'validators' => $passwordValidators,
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
        $form->addRow('email-confirmed', 'option', array(
            'label' => $translator->translate('label.confirmed'),
            'description' => $translator->translate('label.confirmed.email.description'),
        ));
        $form->addRow('image', 'image', array(
            'label' => $translator->translate('label.image'),
            'path' => $fileBrowser->getApplicationDirectory()->getChild('data/upload/profile')->getAbsolutePath(),
        ));
        $form->addRow('active', 'option', array(
            'label' => $translator->translate('label.active'),
            'description' => $translator->translate('label.active.user.description'),
        ));
        $form->addRow('roles', 'option', array(
            'label' => $translator->translate('label.roles'),
            'multiple' => true,
            'options' => $roleOptions,
        ));

        $form = $form->build();
        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $user->setUserName($data['username']);
                $user->setDisplayName($data['name']);
                $user->setEmail($data['email']);
                $user->setIsEmailConfirmed($data['email-confirmed']);
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

        $detailDecorator = new DataDecorator($reflectionHelper, $detailAction);
        $detailDecorator->mapProperty('teaser', 'weight');

        $table = new RoleTable($this->securityModel, $reflectionHelper);
        $table->addDecorator($detailDecorator);
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

            $permissions = $role->getPermissions();
            foreach ($permissions as $index => $permission) {
                unset($permissions[$index]);
                $permissions[$permission->getCode()] = $permission->getCode();
            }

            $data = array(
                'name' => $role->getName(),
                'weight' => $role->getWeight(),
                'allowed-paths' => $this->getPathsString($role->getPaths()),
                'granted-permissions' => $permissions,
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
        $form->addRow('weight', 'integer', array(
            'label' => $translator->translate('label.weight'),
            'description' => $translator->translate('label.weight.role.description'),
            'filters' => array(
                'trim' => array(),
            ),
            'validators' => array(
                'numeric' => array(),
            ),
        ));
        $form->addRow('allowed-paths', 'text', array(
            'label' => $translator->translate('label.paths.allowed'),
            'description' => $translator->translate('label.path.security.description'),
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
                $role->setWeight($data['weight']);
                $this->securityModel->saveRole($role);
                $this->securityModel->setAllowedPathsToRole($role, $data['allowed-paths']);
                if ($permissions) {
                    $this->securityModel->setGrantedPermissionsToRole($role, $data['granted-permissions']);
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
     * Action to get an overview of the permissions
     * @return null
     */
    public function permissionsAction(ReflectionHelper $reflectionHelper) {
        $translator = $this->getTranslator();

        // $detailAction = $this->getUrl('system.security.permission.edit', array('id' => '%id%'));
        // $detailAction .= '?referer=' . urlencode($this->request->getUrl());

        $detailDecorator = new DataDecorator($reflectionHelper); //, $detailAction);
        $detailDecorator->mapProperty('id', 'code');

        $table = new PermissionTable($this->securityModel->getPermissions(), $reflectionHelper);
        $table->addDecorator($detailDecorator);
        $table->setPaginationOptions(array(5, 10, 25, 50, 100, 250, 500));
        $table->addAction(
            $translator->translate('button.delete'),
            array($this, 'deletePermissions'),
            $translator->translate('label.table.confirm.delete')
        );

        $baseUrl = $this->getUrl('system.security.permission');
        $rowsPerPage = 10;

        $form = $this->processTable($table, $baseUrl, $rowsPerPage);
        if ($this->response->willRedirect() || $this->response->getView()) {
            return;
        }

        $this->setTemplateView('base/permissions', array(
            'form' => $form->getView(),
            'table' => $table,
        ));
    }

    /**
     * Action to delete the data from the model
     * @param array $data Array of primary keys
     * @return null
     */
    public function deletePermissions($data) {
        if (!$data) {
            return;
        }

        $referer = $this->request->getHeader(Header::HEADER_REFERER);
        if (!$referer) {
            $referer = $this->request->getUrl();
        }

        $this->response->setRedirect($referer);

        foreach ($data as $code) {
            if (!$this->securityModel->hasPermission($code)) {
                continue;
            }

            $this->securityModel->deletePermission($code);

            $this->addSuccess('success.data.deleted', array('data' => $code));
        }
    }

    /**
     * Action to add or edit a role
     * @param string $id If of the role to edit
     * @return null
          */
    public function permissionFormAction($id = null) {
        if ($id) {
            $permissions = $this->securityModel->getPermissions();
            if (!isset($permissions[$id])) {
                $this->response->setStatusCode(Response::STATUS_CODE_NOT_FOUND);

                return;
            }

            $data = array(
                'code' => $id,
            );
        } else {
            $data = array();
        }

        $referer = $this->request->getQueryParameter('referer');
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($data);
        $form->setId('form-permission');
        $form->addRow('code', 'string', array(
            'label' => $translator->translate('label.code'),
            'filters' => array(
                'trim' => array(),
                'replace' => array(
                    'search' => ' ',
                    'replace' => '',
                ),
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $this->securityModel->addPermission($data['code']);

                $this->addSuccess('success.data.saved', array('data' => $data['code']));

                if (!$referer) {
                    $referer = $this->getUrl('system.security.permission');
                }
                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('base/permission.form', array(
            'form' => $form->getView(),
            'permission' => $id,
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
        $permissions = $this->securityModel->getPermissions();
        $securedPaths = $this->securityModel->getSecuredPaths();

        $data = array(
            'allowed-paths' => array(),
            'secured-paths' => $this->getPathsString($securedPaths),
        );

        foreach ($roles as $role) {
            $data['allowed-paths'][$role->getId()] = $this->getPathsString($role->getPaths());

            $rolePermissions = $role->getPermissions();
            foreach ($rolePermissions as $index => $permission) {
                unset($rolePermissions[$index]);
                $rolePermissions[$permission->getCode()] = $permission;
            }

            $data['role_' . $role->getId()] = $rolePermissions;
        }

        $form = $this->createFormBuilder($data);
        if ($permissions) {
            foreach ($roles as $role) {
                $form->addRow('role_' . $role->getId(), 'option', array(
                    'label' => $role->getName(),
                    'multiple' => true,
                    'options' => $permissions,
                ));
            }
        }
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

                    if ($permissions) {
                        if (isset($data['role_' . $roleId])) {
                            $permissions = array_keys($data['role_' . $roleId]);
                        } else {
                            $permissions = array();
                        }

                        $this->securityModel->setGrantedPermissionsToRole($role, $permissions);
                    }
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
            'permissions' => $permissions,
        ));
    }

    /**
     * Gets the role weight of the current user
     * @return integer
     */
    protected function getUserWeight() {
        $user = $this->getUser();
        if (!$user) {
            return -1;
        }

        return $user->getRoleWeight();
    }

    /**
     * Gets a path string for an array of routes
     * @param array $paths Array with a path as value
     * @return string
     */
    protected function getPathsString(array $paths) {
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
