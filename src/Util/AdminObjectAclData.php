<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\AdminBundle\Util;

use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Security\Handler\SecurityHandlerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Security\Acl\Domain\Acl;

/**
 * AdminObjectAclData holds data manipulated by {@link AdminObjectAclManipulator}.
 *
 * @final since sonata-project/admin-bundle 3.x
 *
 * @author Kévin Dunglas <kevin@les-tilleuls.coop>
 */
class AdminObjectAclData
{
    /**
     * @var array Permissions managed only by a OWNER
     */
    protected static $ownerPermissions = ['MASTER', 'OWNER'];

    /**
     * @var AdminInterface
     */
    protected $admin;

    /**
     * @var object
     */
    protected $object;

    /**
     * @var \Traversable Users to set ACL for
     */
    protected $aclUsers;

    /**
     * @var \Traversable Roles to set ACL for
     */
    protected $aclRoles;

    /**
     * @var array Cache of masks
     */
    protected $masks;

    /**
     * @var Form
     */
    protected $aclUsersForm;

    /**
     * @var Form
     */
    protected $aclRolesForm;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var string
     */
    protected $maskBuilderClass;

    /**
     * @param object $object
     * @param string $maskBuilderClass
     */
    public function __construct(
        AdminInterface $admin,
        $object,
        \Traversable $aclUsers,
        $maskBuilderClass,
        \Traversable $aclRoles = null
    ) {
        $this->admin = $admin;
        $this->object = $object;
        $this->aclUsers = $aclUsers;
        $this->aclRoles = (null === $aclRoles) ? new \ArrayIterator() : $aclRoles;
        $this->maskBuilderClass = $maskBuilderClass;

        $this->updateMasks();
    }

    /**
     * Gets admin.
     *
     * @return AdminInterface
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Gets object.
     *
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Gets ACL users.
     *
     * @return \Traversable
     */
    public function getAclUsers()
    {
        return $this->aclUsers;
    }

    /**
     * Gets ACL roles.
     *
     * @return \Traversable
     */
    public function getAclRoles()
    {
        return $this->aclRoles;
    }

    /**
     * Sets ACL.
     *
     * @return AdminObjectAclData
     */
    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;

        return $this;
    }

    /**
     * Gets ACL.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Gets masks.
     *
     * @return array
     */
    public function getMasks()
    {
        return $this->masks;
    }

    /**
     * Sets form.
     *
     * NEXT_MAJOR: remove this method.
     *
     * @return AdminObjectAclData
     *
     * @deprecated Deprecated since version 3.0. Use setAclUsersForm() instead
     */
    public function setForm(Form $form)
    {
        @trigger_error(
            'setForm() is deprecated since version 3.0 and will be removed in 4.0. '
            .'Use setAclUsersForm() instead.',
            E_USER_DEPRECATED
        );

        return $this->setAclUsersForm($form);
    }

    /**
     * Gets form.
     *
     * NEXT_MAJOR: remove this method.
     *
     * @return Form
     *
     * @deprecated Deprecated since version 3.0. Use getAclUsersForm() instead
     */
    public function getForm()
    {
        @trigger_error(
            'getForm() is deprecated since version 3.0 and will be removed in 4.0. '
            .'Use getAclUsersForm() instead.',
            E_USER_DEPRECATED
        );

        return $this->getAclUsersForm();
    }

    /**
     * Sets ACL users form.
     *
     * @return AdminObjectAclData
     */
    public function setAclUsersForm(Form $form)
    {
        $this->aclUsersForm = $form;

        return $this;
    }

    /**
     * Gets ACL users form.
     *
     * @return Form
     */
    public function getAclUsersForm()
    {
        return $this->aclUsersForm;
    }

    /**
     * Sets ACL roles form.
     *
     * @return AdminObjectAclData
     */
    public function setAclRolesForm(Form $form)
    {
        $this->aclRolesForm = $form;

        return $this;
    }

    /**
     * Gets ACL roles form.
     *
     * @return Form
     */
    public function getAclRolesForm()
    {
        return $this->aclRolesForm;
    }

    /**
     * Gets permissions.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->admin->getSecurityHandler()->getObjectPermissions();
    }

    /**
     * Get permissions that the current user can set.
     *
     * @return array
     */
    public function getUserPermissions()
    {
        $permissions = $this->getPermissions();

        if (!$this->isOwner()) {
            foreach (self::$ownerPermissions as $permission) {
                $key = array_search($permission, $permissions, true);
                if (false !== $key) {
                    unset($permissions[$key]);
                }
            }
        }

        return $permissions;
    }

    public function getOwnerPermissions()
    {
        return self::$ownerPermissions;
    }

    /**
     * Tests if the current user has the OWNER right.
     *
     * @return bool
     */
    public function isOwner()
    {
        // Only a owner can set MASTER and OWNER ACL
        return $this->admin->isGranted('OWNER', $this->object);
    }

    /**
     * Gets security handler.
     *
     * @return SecurityHandlerInterface
     */
    public function getSecurityHandler()
    {
        return $this->admin->getSecurityHandler();
    }

    /**
     * @return array
     */
    public function getSecurityInformation()
    {
        return $this->admin->getSecurityHandler()->buildSecurityInformation($this->admin);
    }

    /**
     * Cache masks.
     */
    protected function updateMasks()
    {
        $permissions = $this->getPermissions();

        $reflectionClass = new \ReflectionClass(new $this->maskBuilderClass());
        $this->masks = [];
        foreach ($permissions as $permission) {
            $this->masks[$permission] = $reflectionClass->getConstant('MASK_'.$permission);
        }
    }
}
