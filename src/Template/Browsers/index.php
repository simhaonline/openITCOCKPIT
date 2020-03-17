<?php
// Copyright (C) <2015>  <it-novum GmbH>
//
// This file is dual licensed
//
// 1.
//	This program is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, version 3 of the License.
//
//	This program is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with this program.  If not, see <http://www.gnu.org/licenses/>.
//

// 2.
//	If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//	under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//	License agreement and license key will be shipped with the order
//	confirmation.
?>
<ol class="breadcrumb page-breadcrumb">
    <li ng-class="{active: container.key == containerId}" ng-repeat="container in breadcrumbs" class="breadcrumb-item">
        <a ng-show="container.key == containerId">
            {{container.value}}
        </a>

        <a ng-click="changeContainerId(container.key)"
           class="pointer"
           style="color:#337ab7;"
           ng-show="container.key != containerId">
            {{container.value}}
        </a>
    </li>
</ol>
<massdelete></massdelete>
<massdeactivate></massdeactivate>

<query-handler-directive></query-handler-directive>


<div class="row">
    <!-- Nodes -->
    <div class="col-xl-4">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Nodes'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <!-- Filter  -->
                    <div class="col-lg-12 margin-bottom-10">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                </div>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="<?php echo __('Type to filter...'); ?>"
                                       ng-model="data.containerFilter"
                                       ng-model-options="{debounce: 250}">
                            </div>
                        </div>
                        <div class="padding-10">
                            <div class="widget-body">
                                <div ng-repeat="container in containers">
                                    <i class="fa fa-home" ng-show="containerId === 1"></i>
                                    <i class="fa fa-link" ng-show="containerId !== 1"></i>
                                    <span ng-click="changeContainerId(container.key)" class="pointer"
                                          style="color:#337ab7;">
                                {{container.value}}
                            </span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Host status overview (graph) -->
    <div class="col-xl-4">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Host status overview'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="text-muted padding-top-20" ng-show="hoststatusSum === 0">
                        <?php echo __('No hosts associated with this node'); ?>
                    </div>

                    <div class="text-center" ng-show="hoststatusSum > 0">
                        <img
                            ng-src="/angular/getPieChart/{{hoststatusCountHash[0]}}/{{hoststatusCountHash[1]}}/{{hoststatusCountHash[2]}}.png">

                        <div class="row text-center font-xs">
                            <div class="col-xs-12 col-md-4 no-padding">
                                <a ui-sref="HostsIndex({hoststate: [0], sort: 'Hoststatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square up"></i>
                                    {{hoststatusCountHash[0]}} ({{hoststatusCountPercentage[0]}} %)
                                </a>
                            </div>

                            <div class="col-xs-12 col-md-4 no-padding">
                                <a ui-sref="HostsIndex({hoststate: [1], sort: 'Hoststatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square down"></i>
                                    {{hoststatusCountHash[1]}} ({{hoststatusCountPercentage[1]}} %)
                                </a>
                            </div>

                            <div class="col-xs-12 col-md-4 no-padding">
                                <a ui-sref="HostsIndex({hoststate: [2], sort: 'Hoststatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square unreachable"></i>
                                    {{hoststatusCountHash[2]}} ({{hoststatusCountPercentage[2]}} %)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service status overview (graph) -->
    <div class="col-xl-4">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Service status overview'); ?>
                </h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    <div class="text-center" ng-show="servicestatusSum > 0">
                        <img
                            ng-src="/angular/getPieChart/{{servicestatusCountHash[0]}}/{{servicestatusCountHash[1]}}/{{servicestatusCountHash[2]}}/{{servicestatusCountHash[3]}}/.png">

                        <div class="row text-center font-xs">
                            <div class="col-xs-12 col-md-3 no-padding">
                                <a ui-sref="ServicesIndex({servicestate: [0], sort: 'Servicestatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square ok"></i>
                                    {{servicestatusCountHash[0]}} ({{servicestatusCountPercentage[0]}} %)
                                </a>
                            </div>

                            <div class="col-xs-12 col-md-3 no-padding">
                                <a ui-sref="ServicesIndex({servicestate: [1], sort: 'Servicestatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square warning"></i>
                                    {{servicestatusCountHash[1]}} ({{servicestatusCountPercentage[1]}} %)
                                </a>
                            </div>

                            <div class="col-xs-12 col-md-3 no-padding">
                                <a ui-sref="ServicesIndex({servicestate: [2], sort: 'Servicestatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square critical"></i>
                                    {{servicestatusCountHash[2]}} ({{servicestatusCountPercentage[2]}} %)
                                </a>
                            </div>

                            <div class="col-xs-12 col-md-3 no-padding">
                                <a ui-sref="ServicesIndex({servicestate: [3], sort: 'Servicestatus.last_state_change', direction: 'desc', BrowserContainerId: containerId})">
                                    <i class="fa fa-square unknown"></i>
                                    {{servicestatusCountHash[3]}} ({{servicestatusCountPercentage[3]}} %)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if ($this->Acl->hasPermission('add', 'hostgroups')): ?>
    <add-hosts-to-hostgroup></add-hosts-to-hostgroup>
<?php endif; ?>


<!-- Host panel -->
<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>
                    <?php echo __('Hosts'); ?>
                    <span class="fw-300"><i><?php echo __('browser'); ?></i></span>
                </h2>
                <div class="panel-toolbar">
                    <button class="btn btn-xs btn-default mr-1 shadow-0" ng-click="load()">
                        <i class="fas fa-sync"></i> <?php echo __('Refresh'); ?>
                    </button>
                    <?php if ($this->Acl->hasPermission('add', 'hosts')): ?>
                        <button class="btn btn-xs btn-success mr-1 shadow-0" ui-sref="HostsAdd">
                            <i class="fas fa-plus"></i> <?php echo __('New'); ?>
                        </button>
                    <?php endif; ?>

                    <button class="btn btn-xs btn-primary shadow-0" ng-click="triggerFilter()">
                        <i class="fas fa-filter"></i> <?php echo __('Filter'); ?>
                    </button>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content">

                    <!-- Start Filter -->
                    <div class="list-filter card margin-bottom-10" ng-show="showFilter">
                        <div class="card-header">
                            <i class="fa fa-filter"></i> <?php echo __('Filter'); ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by host name'); ?>"
                                                   ng-model="filter.Host.name"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-desktop"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by IP address'); ?>"
                                                   ng-model="filter.Host.address"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm"
                                                   placeholder="<?php echo __('Filter by output'); ?>"
                                                   ng-model="filter.Hoststatus.output"
                                                   ng-model-options="{debounce: 500}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6 margin-bottom-10">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                            </div>
                                            <div class="col tagsinputFilter">
                                                <input type="text"
                                                       class="form-control form-control-sm "
                                                       data-role="tagsinput"
                                                       id="HostKeywordsInput"
                                                       placeholder="<?php echo __('Filter by tags'); ?>"
                                                       ng-model="filter.Host.keywords"
                                                       ng-model-options="{debounce: 500}"
                                                       style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Service status'); ?></h5>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUp"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.current_state.up"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-up"
                                                       for="statusFilterUp"><?php echo __('Up'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterDown"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.current_state.down"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-down"
                                                       for="statusFilterDown"><?php echo __('Down'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="statusFilterUnreachable"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.current_state.unreachable"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label custom-control-label-unreachable"
                                                       for="statusFilterUnreachable"><?php echo __('Unreachable'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>


                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Acknowledgements'); ?></h5>
                                        <div class="form-group smart-form">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="ackFilterAck"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.acknowledged"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="ackFilterAck"><?php echo __('Acknowledge'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="ackFilterNotAck"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.not_acknowledged"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="ackFilterNotAck"><?php echo __('Not acknowledged'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-12 col-lg-3">
                                    <fieldset>
                                        <h5><?php echo __('Downtimes'); ?></h5>
                                        <div class="form-group smart-form">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="downtimwFilterInDowntime"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.in_downtime"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="downtimwFilterInDowntime"><?php echo __('In downtime'); ?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       id="downtimwFilterNotInDowntime"
                                                       class="custom-control-input"
                                                       name="checkbox"
                                                       checked="checked"
                                                       ng-model="filter.Hoststatus.not_in_downtime"
                                                       ng-model-options="{debounce: 500}">
                                                <label class="custom-control-label"
                                                       for="downtimwFilterNotInDowntime"><?php echo __('Not in downtime'); ?></label>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>


                            </div>
                            <div class="float-right">
                                <button type="button" ng-click="resetFilter()"
                                        class="btn btn-xs btn-danger">
                                    <?php echo __('Reset Filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End Filter -->
                    <div class="frame-wrap">
                        <table class="table table-striped m-0 table-bordered table-hover table-sm">
                            <thead>
                            <tr>
                                <th colspan="2" class="no-sort width-95" ng-click="orderBy('Hoststatus.current_state')">
                                    <i class="fa" ng-class="getSortClass('Hoststatus.current_state')"></i>
                                    <?php echo __('Hoststatus'); ?>
                                </th>

                                <th class="no-sort text-center">
                                    <i class="fa fa-user" title="<?php echo __('is acknowledged'); ?>"></i>
                                </th>

                                <th class="no-sort text-center">
                                    <i class="fa fa-power-off"
                                       title="<?php echo __('is in downtime'); ?>"></i>
                                </th>

                                <th class="no-sort text-center">
                                    <i class="fa fa fa-area-chart" title="<?php echo __('Grapher'); ?>"></i>
                                </th>

                                <th class="no-sort text-center">
                                    <i title="<?php echo __('Shared'); ?>" class="fa fa-sitemap"></i>
                                </th>

                                <th class="no-sort text-center">
                                    <strong title="<?php echo __('Passively transferred host'); ?>">P</strong>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Hosts.name')">
                                    <i class="fa" ng-class="getSortClass('Hosts.name')"></i>
                                    <?php echo __('Host name'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Hosts.address')">
                                    <i class="fa" ng-class="getSortClass('Hosts.address')"></i>
                                    <?php echo __('IP address'); ?>
                                </th>

                                <th class="no-sort tableStatewidth"
                                    ng-click="orderBy('Hoststatus.last_state_change')">
                                    <i class="fa" ng-class="getSortClass('Hoststatus.last_state_change')"></i>
                                    <?php echo __('Last state change'); ?>
                                </th>

                                <th class="no-sort tableStatewidth" ng-click="orderBy('Hoststatus.last_check')">
                                    <i class="fa" ng-class="getSortClass('Hoststatus.last_check')"></i>
                                    <?php echo __('Last check'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Hoststatus.output')">
                                    <i class="fa" ng-class="getSortClass('Hoststatus.output')"></i>
                                    <?php echo __('Host output'); ?>
                                </th>

                                <th class="no-sort" ng-click="orderBy('Hosts.satellite_id')">
                                    <i class="fa" ng-class="getSortClass('Hosts.satellite_id')"></i>
                                    <?php echo __('Instance'); ?>
                                </th>

                                <th class="no-sort text-center editItemWidth"><i class="fa fa-gear"></i></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="host in hosts">
                                <td class="width-5">
                                    <input type="checkbox"
                                           ng-model="massChange[host.Host.id]"
                                           ng-show="host.Host.allow_edit">
                                </td>

                                <td class="text-center">
                                    <hoststatusicon host="host"></hoststatusicon>
                                </td>

                                <td class="text-center">
                                    <i class="far fa-user"
                                       ng-show="host.Hoststatus.problemHasBeenAcknowledged"
                                       ng-if="host.Hoststatus.acknowledgement_type == 1"></i>

                                    <i class="fas fa-user"
                                       ng-show="host.Hoststatus.problemHasBeenAcknowledged"
                                       ng-if="host.Hoststatus.acknowledgement_type == 2"
                                       title="<?php echo __('Sticky Acknowledgedment'); ?>"></i>
                                </td>

                                <td class="text-center">
                                    <i class="fa fa-power-off"
                                       ng-show="host.Hoststatus.scheduledDowntimeDepth > 0"></i>
                                </td>

                                <td class="text-center">
                                    <?php if ($this->Acl->hasPermission('serviceList', 'services')): ?>
                                        <a ui-sref="ServicesServiceList({id: host.Host.id})"
                                           class="txt-color-blueDark"
                                           ng-if="host.Host.has_graphs">
                                            <i class="fa fa-area-chart"></i>
                                        </a>
                                    <?php else: ?>
                                        <i class="fa fa-area-chart txt-color-blueDark"
                                           ng-if="host.Host.has_graphs">
                                        </i>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">

                                    <a class="txt-color-blueDark" title="<?php echo __('Shared'); ?>"
                                       ng-if="host.Host.allow_sharing === true && host.Host.containerIds.length > 1"
                                       ui-sref="HostsSharing({id:host.Host.id})">
                                        <i class="fa fa-sitemap"></i></a>

                                    <i class="fa fa-low-vision txt-color-blueLight"
                                       ng-if="host.Host.allow_sharing === false && host.Host.containerIds.length > 1"
                                       title="<?php echo __('Restricted view'); ?>"></i>
                                </td>

                                <td class="text-center">
                                    <strong title="<?php echo __('Passively transferred host'); ?>"
                                            ng-show="host.Host.active_checks_enabled === false || host.Host.is_satellite_host === true">
                                        P
                                    </strong>
                                </td>

                                <td>
                                    <?php if ($this->Acl->hasPermission('browser', 'hosts')): ?>
                                        <a ui-sref="HostsBrowser({id:host.Host.id})">
                                            {{ host.Host.hostname }}
                                        </a>
                                    <?php else: ?>
                                        {{ host.Host.hostname }}
                                    <?php endif; ?>
                                </td>

                                <td>
                                    {{ host.Host.address }}
                                </td>

                                <td>
                                    {{ host.Hoststatus.last_state_change }}
                                </td>

                                <td>
                                    {{ host.Hoststatus.lastCheck }}
                                </td>

                                <td>
                                    {{ host.Hoststatus.output }}
                                </td>

                                <td>
                                    {{ host.Host.satelliteName }}
                                </td>

                                <td class="width-50">
                                    <div class="btn-group btn-group-xs" role="group">
                                        <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                            <a ui-sref="HostsEdit({id: host.Host.id})"
                                               ng-if="host.Host.allow_edit"
                                               class="btn btn-default btn-lower-padding">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                            <a href="javascript:void(0);"
                                               ng-if="!host.Host.allow_edit"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="javascript:void(0);"
                                               class="btn btn-default btn-lower-padding disabled">
                                                <i class="fa fa-cog"></i></a>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-default dropdown-toggle btn-lower-padding"
                                                data-toggle="dropdown">
                                            <i class="caret"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <?php if ($this->Acl->hasPermission('edit', 'hosts')): ?>
                                                <a ui-sref="HostsEdit({id: host.Host.id})"
                                                   ng-if="host.Host.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-cog"></i>
                                                    <?php echo __('Edit'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('sharing', 'hosts')): ?>
                                                <a ui-sref="HostsSharing({id:host.Host.id})"
                                                   ng-if="host.Host.allow_edit"
                                                   class="dropdown-item">
                                                    <i class="fa fa-sitemap fa-rotate-270"></i>
                                                    <?php echo __('Sharing'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('serviceList', 'services')): ?>
                                                <a ui-sref="ServicesServiceList({id: host.Host.id})"
                                                   class="dropdown-item">
                                                    <i class="fa fa-list"></i>
                                                    <?php echo __('Service List'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('allocateToHost', 'servicetemplategroups')): ?>
                                                <a ui-sref="ServicetemplategroupsAllocateToHost({id: 0, hostId: host.Host.id})"
                                                   class="dropdown-item">
                                                    <i class="fa fa-external-link-alt"></i>
                                                    <?php echo __('Allocate service template group'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($this->Acl->hasPermission('add', 'hostgroups', '')): ?>
                                                <a ng-click="confirmAddHostsToHostgroup(getObjectForDelete(host))"
                                                   class="dropdown-item" href="javascript:void(0);">
                                                    <i class="fa fa-sitemap"></i>
                                                    <?php echo __('Append to host group'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('deactivate', 'hosts')): ?>
                                                <a ng-if="host.Host.allow_edit"
                                                   class="dropdown-item"
                                                   href="javascript:void(0);"
                                                   ng-click="confirmDeactivate(getObjectForDelete(host))">
                                                    <i class="fa fa-plug"></i>
                                                    <?php echo __('Disable'); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->Acl->hasPermission('delete', 'hosts')): ?>
                                                <div class="dropdown-divider"></div>
                                                <a ng-click="confirmDelete(getObjectForDelete(host))"
                                                   ng-if="host.Host.allow_edit"
                                                   class="dropdown-item txt-color-red">
                                                    <i class="fa fa-trash"></i>
                                                    <?php echo __('Delete'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                            </tr>
                            </tbody>
                        </table>
                        <div class="margin-top-10" ng-show="hosts.length == 0">
                            <div class="text-center text-danger italic">
                                <?php echo __('No entries match the selection'); ?>
                            </div>
                        </div>
                        <div class="row margin-top-10 margin-bottom-10">
                            <div class="col-xs-12 col-md-2 text-muted text-center">
                                <span ng-show="selectedElements > 0">({{selectedElements}})</span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="selectAll()" class="pointer">
                                    <i class="fas fa-check-square"></i>
                                    <?php echo __('Select all'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <span ng-click="undoSelection()" class="pointer">
                                    <i class="fas fa-square"></i>
                                    <?php echo __('Undo selection'); ?>
                                </span>
                            </div>
                            <div class="col-xs-12 col-md-2">
                                <a ui-sref="ServicesCopy({ids: linkForCopy()})" class="a-clean">
                                    <i class="fas fa-files-o"></i>
                                    <?php echo __('Copy'); ?>
                                </a>
                            </div>
                            <div class="col-xs-12 col-md-2 txt-color-red">
                                <span ng-click="confirmDelete(getObjectsForDelete())" class="pointer">
                                    <i class="fas fa-trash"></i>
                                    <?php echo __('Delete all'); ?>
                                </span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-default dropdown-toggle waves-effect waves-themed" type="button"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php echo __('More actions'); ?>
                                </button>
                                <div class="dropdown-menu" x-placement="bottom-start"
                                     style="position: absolute; will-change: top, left; top: 37px; left: 0px;">
                                    <?php if ($this->Acl->hasPermission('edit_details', 'Hosts', '')): ?>
                                        <a ui-sref="HostsEditDetails({ids: linkForEditDetails()})"
                                           class="dropdown-item">
                                            <i class="fa fa-cog"></i> <?php echo __('Edit details'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('deactivate', 'Hosts', '')): ?>
                                        <a ng-click="confirmDeactivate(getObjectsForDelete())"
                                           class="dropdown-item" href="javascript:void(0);">
                                            <i class="fa fa-plug"></i> <?php echo __('Disable'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('add', 'hostgroups', '')): ?>
                                        <a ng-click="confirmAddHostsToHostgroup(getObjectsForDelete())"
                                           class="dropdown-item" href="javascript:void(0);">
                                            <i class="fa fa-sitemap"></i> <?php echo __('Append to host group'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($this->Acl->hasPermission('externalcommands', 'hosts')): ?>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="rescheduleHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-refresh"></i> <?php echo __('Reset check time'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="disableHostNotifications(getObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i> <?php echo __('Disable notification'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="enableHostNotifications(getObjectsForExternalCommand())">
                                            <i class="fa fa-envelope"></i> <?php echo __('Enable notifications'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="hostDowntime(getObjectsForExternalCommand())">
                                            <i class="fa fa-clock-o"></i> <?php echo __('Set planned maintenance times'); ?>
                                        </a>
                                        <a class="dropdown-item" href="javascript:void(0);"
                                           ng-click="acknowledgeHost(getObjectsForExternalCommand())">
                                            <i class="fa fa-user"></i> <?php echo __('Acknowledge host status'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>


                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>


                    </div>
                </div>
            </div>
            <reschedule-host></reschedule-host>
            <disable-host-notifications></disable-host-notifications>
            <enable-host-notifications></enable-host-notifications>
            <acknowledge-host author="<?php echo h($username); ?>"></acknowledge-host>
            <host-downtime author="<?php echo h($username); ?>"></host-downtime>
        </div>
    </div>
</div>
