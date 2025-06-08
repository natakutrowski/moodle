@block @block_gearup @javascript
Feature: Test recruits filtering by group
  In order to manage my recruits
  As a teacher
  I need to be able to filter by groups

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname | groupmode |
      | Course 1  | c1        | 1         |
    And the following "groups" exist:
      | name     | course | idnumber  |
      | Group 1  | c1     | G1        |
      | Group 2  | c1     | G2        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Fatal     | Bazooka  |
      | s2       | Chris     | Prolls   |
      | s3       | Athena    | Novotel  |
      | s4       | David     | Fontana  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
      | s2       | c1     | student        |
      | s3       | c1     | student        |
      | s4       | c1     | student        |
    And the following "group members" exist:
      | group | user |
      | G1    | s1   |
      | G1    | s2   |
      | G2    | s3   |
      | G2    | s4   |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > achievements" exist:
      | title              | course | instructions       |
      | Ultra Achievement  | c1     | This is what to do |
    And the following "block_gearup > recruits" exist:
      | mission              | user   |
      | Ultra Achievement    | s1     |
      | Ultra Achievement    | s2     |
      | Ultra Achievement    | s3     |
      | Ultra Achievement    | s4     |
    And I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I follow "Fatal Bazooka"
    And I press "Increment"
    And I press "Confirm"

  Scenario: A mission's insights page can be filtered by groups
    Given I am on the "Ultra Achievement" "block_gearup > achievement insights" page logged in as "t1"
    And I should see "4" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "25%" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"
    When I set the field "group" to "Group 1"
    Then I should see "2" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "50%" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"
    And I set the field "group" to "Group 2"
    And I should see "2" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "0%" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"

  Scenario: The overall insights page can be filtered by groups
    Given I am on the "c1" "block_gearup > insights" page logged in as "t1"
    And I should see "4" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "1" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"
    When I set the field "group" to "Group 1"
    Then I should see "2" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "1" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"
    And I set the field "group" to "Group 2"
    And I should see "2" in the "//dt[normalize-space(text())='Recruits']/following-sibling::dd" "xpath"
    And I should see "0" in the "//dt[normalize-space(text())='Completed']/following-sibling::dd" "xpath"
