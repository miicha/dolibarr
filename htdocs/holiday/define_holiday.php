<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Dimitri Mouillard <dmouillard@teclib.com>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *		File that defines the balance of paid holiday of users.
 *
 *   	\file       define_holiday.php
 *		\ingroup    holiday
 *		\brief      File that defines the balance of paid holiday of users.
 *		\author		dmouillard@teclib.com <Dimitri Mouillard>
 */

require('pre.inc.php');
require_once(DOL_DOCUMENT_ROOT. "/user/class/user.class.php");

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

// Si l'utilisateur n'a pas le droit de lire cette page
if(!$user->rights->holiday->define_holiday) accessforbidden();

$action=GETPOST('action');


/*
 * View
 */

llxHeader($langs->trans('CPTitreMenu'));

print_fiche_titre($langs->trans('MenuConfCP'));

$holiday = new Holiday($db);
$listUsers = $holiday->fetchUsers(false,false);
$userstatic=new User($db);

// Si il y a une action de mise à jour
if ($action == 'update' && isset($_POST['update_cp']))
{
    $userID = array_keys($_POST['update_cp']);
    $userID = $userID[0];

    $userValue = $_POST['nb_holiday'];
    $userValue = $userValue[$userID];

    if(!empty($userValue)) {
        $userValue = price2num($userValue,2);
    } else {
        $userValue = 0;
    }

    // On ajoute la modification dans le LOG
    $holiday->addLogCP($user->id,$userID,'Event : Manual update',$userValue);

    // Mise à jour des congés de l'utilisateur
    $holiday->updateSoldeCP($userID,$userValue);


    $mesg='<div class="ok">'.$langs->trans('UpdateConfCPOK').'</div>';

    dol_htmloutput_mesg($mesg);

}
elseif($action == 'add_event')
{
    $error = false;

    if(!empty($_POST['list_event']) && $_POST['list_event'] > 0) {
        $event = $_POST['list_event'];
    } else { $error = true;
    }

    if(!empty($_POST['userCP']) && $_POST['userCP'] > 0) {
        $userCP = $_POST['userCP'];
    } else { $error = true;
    }

    if($error) {
        $message = '<div class="error">'.$langs->trans('ErrorAddEventToUserCP').'</div>';
    } else {
        $nb_holiday = $holiday->getCPforUser($userCP);
        $add_holiday = $holiday->getValueEventCp($event);
        $new_holiday = $nb_holiday + $add_holiday;

        // On ajoute la modification dans le LOG
        $holiday->addLogCP($user->id,$userCP,'Event : '.$holiday->getNameEventCp($event),$new_holiday);

        $holiday->updateSoldeCP($userCP,$new_holiday);

        $message = $langs->trans('AddEventToUserOkCP');
    }

    dol_htmloutput_mesg($message);
}

$var=true;
$i = 0;

print '<div class="tabBar">';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="action" value="update" />';
print '<table class="noborder" width="100%;">';
print "<tr class=\"liste_titre\">";
print '<td width="5%">User ID</td>';
print '<td width="20%">'.$langs->trans('UserName').'</td>';
print '<td width="10%">'.$langs->trans('Available').'</td>';
print '<td>'.$langs->trans('UpdateButtonCP').'</td>';
print '</tr>';

foreach($listUsers as $users)
{

    $var=!$var;

    print '<tr '.$bc[$var].' style="height: 20px;">';
    print '<td>'.$users['rowid'].'</td>';
    print '<td>';
    $userstatic->id=$users['rowid'];
    $userstatic->nom=$users['name'];
    $userstatic->prenom=$users['firstname'];
    print $userstatic->getNomUrl(1);
    print '</td>';
    print '<td>';
    print '<input type="text" value="'.$holiday->getCPforUser($users['rowid']).'" name="nb_holiday['.$users['rowid'].']" size="5" style="text-align: center;"/>';
    print ' jours</td>'."\n";
    print '<td><input type="submit" name="update_cp['.$users['rowid'].']" value="'.dol_escape_htmltag($langs->trans("Update")).'" class="button"/></td>'."\n";
    print '</tr>';

    $i++;
}

print '</table>';
print '</form>';

$cp_events = $holiday->fetchEventsCP();

if($cp_events == 1) {

    $html = new Form($db);

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<input type="hidden" name="action" value="add_event" />';

    print '<h3>'.$langs->trans('DefineEventUserCP').'</h3>';
    print $langs->trans('MotifCP').' : ';
    print $holiday->selectEventCP();
    print ' '.$langs->trans('UserCP').' : ';
    print $html->select_users('',"userCP",1,"",0,'');
    print ' <input type="submit" value="'.$langs->trans("addEventToUserCP").'" name="bouton" class="button"/>';


    print '</form>';
}
print '</div>';
// Fin de page
$db->close();
llxFooter();
?>