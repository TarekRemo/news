<?php

/**
 * -------------------------------------------------------------------------
 * News plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of News.
 *
 * News is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * News is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with News. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2015-2023 by News plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/news
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\News\Tests\Units;

use Glpi\Tests\DbTestCase;
use PluginNewsAlert;
use PluginNewsAlert_User;
use User;

class PluginNewsAlertTest extends DbTestCase
{
    private function getAlertUserState(int $au_id): int
    {
        $alert_user = new PluginNewsAlert_User();
        $this->assertTrue($alert_user->getFromDB($au_id));
        return (int) $alert_user->fields['state'];
    }

    public function testPostUpdateItemResetsHiddenAlerts(): void
    {
        $this->login('glpi');

        $alert = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'closable Alert',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $this->assertEquals(PluginNewsAlert::class, get_class($alert));
        $alert_id = $alert->getID();

        $user_1 = $this->createItem(
            User::class,
            [
                'name'      => 'user 1',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );

        $user_2   = $this->createItem(
            User::class,
            [
                'name'      => 'user 2',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );

        $this->assertEquals(User::class, get_class($user_1));
        $this->assertEquals(User::class, get_class($user_2));

        $user_1_id = $user_1->getID();
        $user_2_id = $user_2->getID();

        $alert_user_1 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $alert_user_2 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_id,
            'users_id'              => $user_2_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $this->assertEquals(PluginNewsAlert_User::class, get_class($alert_user_1));
        $this->assertEquals(PluginNewsAlert_User::class, get_class($alert_user_2));

        $alert_user_1_id = $alert_user_1->getID();
        $alert_user_2_id = $alert_user_2->getID();

        $result = $alert->update(['id' => $alert_id, 'name' => 'Alert with hidden users (updated)']);
        $this->assertTrue($result);

        //assert that both users are now in VISIBLE state
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_2_id));

        //re-hide users alerts
        $alert_user = new PluginNewsAlert_User();
        $this->assertTrue($alert_user->update(['id' => $alert_user_1_id, 'state' => PluginNewsAlert_User::HIDDEN]));
        $this->assertTrue($alert_user->update(['id' => $alert_user_2_id, 'state' => PluginNewsAlert_User::HIDDEN]));

        //update the alert with is_close_allowed = 0
        $result = $alert->update(['id' => $alert_id, 'is_close_allowed' => 0]);
        $this->assertTrue($result);

        //assert that alerts are visible again
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_2_id));
    }

    public function testPostUpdateItemDoesNotAffectOtherAlerts(): void
    {
        $this->login('glpi');

        $alert_1 = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'alert 1',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $this->assertEquals(PluginNewsAlert::class, get_class($alert_1));
        $alert_1_id = $alert_1->getID();

        $alert_2 = $this->createItem(
            PluginNewsAlert::class,
            [
                'name'        => 'alert 2',
                'message'     => 'This is a closable alert',
                'type'        => 1,
                'is_displayed_onlogin' => 1,
                'is_displayed_oncentral' => 1,
                'is_displayed_onservicecatalog' => 1,
                'display_dates' => 1,
                'background_color' => 'white',
                'emphasis_color' => 'dark',
                'size' => 'medium',
                'icon' => 'settings',
                'is_displayed_onhelpdesk' => 1,
                'is_active'   => 1,
                'entities_id' => 0,
                'is_close_allowed' => 1,
            ],
        );
        $this->assertEquals(PluginNewsAlert::class, get_class($alert_1));
        $alert_2_id = $alert_2->getID();

        $user_1 = $this->createItem(
            User::class,
            [
                'name'      => 'user 1',
                'password'  => 'test',
                'password2' => 'test',
            ],
            ['password', 'password2'],
        );
        $this->assertEquals(User::class, get_class($user_1));
        $user_1_id = $user_1->getID();

        $alert_user_1 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_1_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $alert_user_2 = $this->createItem(PluginNewsAlert_User::class, [
            'plugin_news_alerts_id' => $alert_2_id,
            'users_id'              => $user_1_id,
            'state'                 => PluginNewsAlert_User::HIDDEN,
        ]);

        $this->assertEquals(PluginNewsAlert_User::class, get_class($alert_user_1));
        $this->assertEquals(PluginNewsAlert_User::class, get_class($alert_user_2));

        $alert_user_1_id = $alert_user_1->getID();
        $alert_user_2_id = $alert_user_2->getID();

        $alert = new PluginNewsAlert();
        $alert->update(['id' => $alert_1_id, 'name' => 'Alert 1 (updated)']);

        $this->assertSame(PluginNewsAlert_User::VISIBLE, $this->getAlertUserState($alert_user_1_id));
        $this->assertSame(PluginNewsAlert_User::HIDDEN, $this->getAlertUserState($alert_user_2_id));
    }
}
