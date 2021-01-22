<?php
/**
 * Copyright © Magento. All rights reserved.
 */
namespace MagentoEse\DataInstall\Model;

use Magento\User\Api\Data\UserInterfaceFactory;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollection;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
use Magento\Authorization\Model\UserContextInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollection;


class AdminUsers
{
    /** @var UserInterfaceFactory */
    protected $userFactory;

    /** @var RoleFactory */
    protected $roleFactory;

    /** @var RoleCollection */
    protected $roleCollection;

    /** @var UserCollection */
    protected $userCollection;

    /**
     * AdminUsers constructor.
     * @param UserInterfaceFactory $userFactory
     * @param RoleFactory $roleFactory
     * @param RoleCollection $roleCollection
     * @param UserCollection $userCollection
     */
    public function __construct(
        UserInterfaceFactory $userFactory,
        RoleFactory $roleFactory,
        RoleCollection $roleCollection,
        UserCollection $userCollection
    ) {
        $this->userFactory = $userFactory;
        $this->roleFactory = $roleFactory;
        $this->roleCollection = $roleCollection;
        $this->userCollection = $userCollection;
    }

    /**
     * @param array $row
     * @param array $settings
     * @return bool
     */
    public function install(array $row, array $settings)
    {
        $user = $this->userCollection->create()->addFieldToFilter('username', ['eq' => $row['username']])->getFirstItem();
            //create role if it doesnt exist
        if (!$user->getData('username')) {
            $user = $this->userFactory->create();       
            $user->setEmail($row['email']);
            $user->setFirstName($row['firstname']);
            $user->setLastName($row['lastname']);
            $user->setUserName($row['username']);
            $user->setPassword($row['password']);
            $user->save();
        }
        $this->addUserToRole($user, $row);

        return true;
    }

    /**
     * @param $user
     * @param array $row
     */
    private function addUserToRole($user, $row)
    {
        if (!empty($row['role'])) {
            $role = $this->roleCollection->create()
            ->addFieldToFilter('role_name', ['eq' => $row['role']])->getFirstItem();
            //create role if it doesnt exist
            if ($role->getData('role_name')) {
                $userRole=$this->roleFactory->create();
                $userRole->setParentId($role->getId());
                $userRole->setTreeLevel(2);
                $userRole->setRoleType('U');
                $userRole->setUserId($user->getId());
                $userRole->setUserType(2);
                $userRole->setRoleName($user->getUserName());
                $userRole->save();
            } else {
                print_r("Role ".$row['role']." for user ".$row['username']." does not exist\n");
            }
        }
    }

    private function createSalesrepRole()
    {
        try {
            $role=$this->roleFactory->create();
            $role->setName('Sales Rep') //Set Role Name Which you want to create
            ->setPid(0) //set parent role id of your role
            ->setRoleType(RoleGroup::ROLE_TYPE)
               ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
            $role->save();

            /* Add resources we allow to this role */
            $resource=['Magento_Backend::admin',
               'Magento_Sales::sales'
            ];
            //save resources to role
            $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resource)->saveRel();

            return $role;
        } catch (\Exception $e) {
            //ignore
        }
    }
}