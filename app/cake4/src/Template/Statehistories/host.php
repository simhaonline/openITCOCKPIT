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
<host-browser-menu
        ng-if="hostBrowserMenuConfig"
        config="hostBrowserMenuConfig"
        last-load-date="0"></host-browser-menu>

<section id="widget-grid" class="">

    <div class="row">

        <article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="jarviswidget jarviswidget-color-blueDark" id="wid-id-1" data-widget-editbutton="false">

                <header>
                    <div class="widget-toolbar" role="menu">
                        <button type="button" class="btn btn-xs btn-default" ng-click="load()">
                            <i class="fa fa-refresh"></i>
                            <?php echo __('Refresh'); ?>
                        </button>

                        <button type="button" class="btn btn-xs btn-primary" ng-click="triggerFilter()">
                            <i class="fa fa-filter"></i>
                            <?php echo __('Filter'); ?>
                        </button>
                    </div>

                    <div class="jarviswidget-ctrls" role="menu"></div>
                    <span class="widget-icon"> <i class="fa fa-history"></i> </span>
                    <h2><?php echo __('Host state history'); ?> </h2>
                </header>


                <div>

                    <div class="widget-body no-padding">
                        <div class="list-filter well" ng-show="showFilter">
                            <h3><i class="fa fa-filter"></i> <?php echo __('Filter'); ?></h3>
                            <div class="row">
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend"
                                                                 style="padding-right:14px;"><?php echo __('From'); ?></i>
                                            <input type="text" class="input-sm" style="padding-left:50px;"
                                                   placeholder="<?php echo __('From Date'); ?>"
                                                   ng-model="filter.from"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend fa fa-filter"></i>
                                            <input type="text" class="input-sm"
                                                   placeholder="<?php echo __('Filter by output'); ?>"
                                                   ng-model="filter.StatehistoryHosts.output"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-md-6">
                                    <div class="form-group smart-form">
                                        <label class="input"> <i class="icon-prepend"
                                                                 style="padding-right:14px;"><?php echo __('To'); ?></i>
                                            <input type="text" class="input-sm" style="padding-left:50px;"
                                                   placeholder="<?php echo __('To Date'); ?>"
                                                   ng-model="filter.to"
                                                   ng-model-options="{debounce: 500}">
                                        </label>
                                    </div>
                                </div>

                            </div>
                            <div class="row">

                                <div class="col-xs-12 col-md-3">
                                    <fieldset>
                                        <legend><?php echo __('States'); ?></legend>
                                        <div class="form-group smart-form">
                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.recovery"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-success"></i>
                                                <?php echo __('Up'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.down"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-danger"></i>
                                                <?php echo __('Down'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state.unreachable"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-default"></i>
                                                <?php echo __('Unreachable'); ?>
                                            </label>
                                        </div>
                                    </fieldset>
                                </div>

                                <div class="col-xs-12 col-md-3">
                                    <fieldset>
                                        <legend><?php echo __('State Types'); ?></legend>
                                        <div class="form-group smart-form">
                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state_types.soft"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Soft'); ?>
                                            </label>

                                            <label class="checkbox small-checkbox-label">
                                                <input type="checkbox" name="checkbox" checked="checked"
                                                       ng-model="filter.StatehistoryHosts.state_types.hard"
                                                       ng-model-options="{debounce: 500}">
                                                <i class="checkbox-primary"></i>
                                                <?php echo __('Hard'); ?>
                                            </label>

                                        </div>
                                    </fieldset>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="pull-right margin-top-10">
                                        <button type="button" ng-click="resetFilter()"
                                                class="btn btn-xs btn-danger">
                                            <?php echo __('Reset Filter'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table id="hoststatehistory_list"
                               class="table table-striped table-hover table-bordered smart-form">
                            <thead>
                            <tr>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state')"></i>
                                    <?php echo __('State'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state_time')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state_time')"></i>
                                    <?php echo __('Date'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.current_check_attempt')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.current_check_attempt')"></i>
                                    <?php echo __('Check attempt'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.state_type')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.state_type')"></i>
                                    <?php echo __('State type'); ?>
                                </th>
                                <th class="no-sort" ng-click="orderBy('StatehistoryHosts.output')">
                                    <i class="fa" ng-class="getSortClass('StatehistoryHosts.output')"></i>
                                    <?php echo __('Host output'); ?>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr ng-repeat="StatehistoryHost in statehistories">

                                <td class="text-center">
                                    <hoststatusicon state="StatehistoryHost.StatehistoryHost.state"></hoststatusicon>
                                </td>
                                <td>
                                    {{ StatehistoryHost.StatehistoryHost.state_time }}
                                </td>
                                <td class="text-center">
                                    {{ StatehistoryHost.StatehistoryHost.current_check_attempt }}/{{
                                    StatehistoryHost.StatehistoryHost.max_check_attempts }}
                                </td>
                                <td class="text-center">
                                        <span ng-show="StatehistoryHost.StatehistoryHost.is_hardstate">
                                            <?php echo __('Hard'); ?>
                                        </span>

                                    <span ng-show="!StatehistoryHost.StatehistoryHost.is_hardstate">
                                            <?php echo __('Soft'); ?>
                                        </span>

                                </td>
                                <td>
                                    {{ StatehistoryHost.StatehistoryHost.output }}
                                </td>
                            </tr>

                            </tbody>
                        </table>

                        <div class="row margin-top-10 margin-bottom-10">
                            <div class="row margin-top-10 margin-bottom-10" ng-show="statehistories.length == 0">
                                <div class="col-xs-12 text-center txt-color-red italic">
                                    <?php echo __('No entries match the selection'); ?>
                                </div>
                            </div>
                        </div>

                        <scroll scroll="scroll" click-action="changepage" ng-if="scroll"></scroll>
                        <paginator paging="paging" click-action="changepage" ng-if="paging"></paginator>
                        <?php echo $this->element('paginator_or_scroll'); ?>
                    </div>
                </div>
            </div>
        </article>
    </div>
</section>