<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, version 3 of the License.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//  If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//  under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//  License agreement and license key will be shipped with the order
//  confirmation.

namespace itnovum\openITCOCKPIT\Core\Views;


class EvcContainerPermissions {

    /**
     * @var array
     */
    private $MY_RIGHTS_LEVEL = [];


    /**
     * @var array
     */
    private $MY_VIEW_RIGHTS_LEVEL = [];

    /**
     * @var array
     */
    private $usedEvcContainerIdsGroupByHost = [];

    /**
     * ContainerPermissions constructor.
     * @param array $MY_RIGHTS_LEVEL
     * @param array $ContainersToCheck
     */
    public function __construct($MY_RIGHTS_LEVEL, $usedEvcContainerIdsGroupByHost = []) {
        $this->MY_VIEW_RIGHTS_LEVEL = $MY_RIGHTS_LEVEL;
        foreach ($MY_RIGHTS_LEVEL as $containerId => $rightLevel) {
            $rightLevel = (int)$rightLevel;
            if ($rightLevel === WRITE_RIGHT) {
                $this->MY_RIGHTS_LEVEL[$containerId] = $rightLevel;
            }
        }
        $this->usedEvcContainerIdsGroupByHost = $usedEvcContainerIdsGroupByHost;
    }

    /**
     * @param int $evcPrimaryContainerId
     * @return bool
     */
    public function hasEditPermission($evcPrimaryContainerId) {
        $evcPrimaryContainerId = (int)$evcPrimaryContainerId;

        $canEdit = false;
        foreach ($this->usedEvcContainerIdsGroupByHost as $hostId => $containers) {
            if (isset($this->usedEvcContainerIdsGroupByHost[$hostId][ROOT_CONTAINER])) {
                unset($this->usedEvcContainerIdsGroupByHost[$hostId][ROOT_CONTAINER]);

                if (empty($this->usedEvcContainerIdsGroupByHost[$hostId])) {
                    //This host had only the ROOT_CONTAINER (allowed for everyone)
                    //Fallback to EVCs primary container id
                    if (isset($this->MY_RIGHTS_LEVEL[$evcPrimaryContainerId])) {
                        $canEdit = $this->MY_RIGHTS_LEVEL[$evcPrimaryContainerId] === WRITE_RIGHT;
                        if ($canEdit === false) {
                            return false;
                        }
                        continue;
                    } else {
                        return false;
                    }
                }

            }
            $containersToCheck = $this->usedEvcContainerIdsGroupByHost[$hostId];

            $canEdit = !empty(array_intersect($containersToCheck, array_keys($this->MY_RIGHTS_LEVEL)));
            if ($canEdit === false) {
                //User is not allowd to edit this host.
                //So whole EVC is not editable for this user.
                return false;
            }
        }
        return $canEdit;
    }

    /**
     * @return bool
     */
    public function hasViewPermission($evcPrimaryContainerId) {
        $evcPrimaryContainerId = (int)$evcPrimaryContainerId;

        $canView = false;
        foreach ($this->usedEvcContainerIdsGroupByHost as $hostId => $containers) {
            if (isset($this->usedEvcContainerIdsGroupByHost[$hostId][ROOT_CONTAINER])) {
                unset($this->usedEvcContainerIdsGroupByHost[$hostId][ROOT_CONTAINER]);

                if (empty($this->usedEvcContainerIdsGroupByHost[$hostId])) {
                    //This host had only the ROOT_CONTAINER (allowed for everyone)
                    //Fallback to EVCs primary container id
                    $canView = isset($this->MY_VIEW_RIGHTS_LEVEL[$evcPrimaryContainerId]);
                    if ($canView === false) {
                        return false;
                    }
                    continue;
                }

            }
            $containersToCheck = $this->usedEvcContainerIdsGroupByHost[$hostId];

            $canView = !empty(array_intersect($containersToCheck, array_keys($this->MY_VIEW_RIGHTS_LEVEL)));
            if ($canView === false) {
                //User is not allowd to edit this host.
                //So whole EVC is not editable for this user.
                return false;
            }
        }
        return $canView;
    }
}
