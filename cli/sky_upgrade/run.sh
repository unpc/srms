#!/bin/bash

export SITE_ID=cf-lite
export LAB_ID=nankai_sky



echo '======= php 1-sky_upgrade_user.php'
php 1-sky_upgrade_user.php 
echo '======= php 2-upgrade_transaction_to_fin_transaction.php'
php 2-upgrade_transaction_to_fin_transaction.php
echo '======= php 3-fix_fin_transactions.php'
php 3-fix_fin_transactions.php
echo '======= php 4-force_active_all_labs.php'
php 4-force_active_all_labs.php
echo '======= php 5-set_lab_members_group.php'
php 5-set_lab_members_group.php
echo '======= php 6-eq.php'
php 6-eq.php
echo '======= php 7-set_equipment_default_department.php'
php 7-set_equipment_default_department.php
echo '======= php 8-set_equipment_incharge_as_contact.php'
php 8-set_equipment_incharge_as_contact.php
echo '======= php 9-switch_lab_tag.ph'
php 9-switch_lab_tag.php


