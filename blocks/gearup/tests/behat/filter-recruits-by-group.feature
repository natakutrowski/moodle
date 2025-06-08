@block @block_gearup
Feature: Test recruits insights by group
  In order to get insights on my missions
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

  Scenario: The main recruits page can be filtered by groups
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I should see "Showing 1 to 4 of 4"
    And I set the field "group" to "Group 1"
    When I press "Apply"
    Then I should see "Showing 1 to 2 of 2"
    And I should see "Fatal Bazooka"
    And I should see "Chris Prolls"
    And I should not see "Athena Novotel"
    And I should not see "David Fontana"
    And I set the field "group" to "Group 2"
    And I press "Apply"
    And I should see "Showing 1 to 2 of 2"
    And I should see "Athena Novotel"
    And I should see "David Fontana"
    And I should not see "Fatal Bazooka"
    And I should not see "Chris Prolls"
    And I set the field "group" to "All participants"
    And I press "Apply"
    And I should see "Showing 1 to 4 of 4"

  Scenario: The main recruits page can be filtered by groups and term
    Given I am on the "c1" "block_gearup > recruits" page logged in as "t1"
    And I should see "Showing 1 to 4 of 4"
    And I set the field "group" to "Group 1"
    And I set the field "term" to "f b"
    When I press "Apply"
    Then I should see "Showing 1 to 1 of 1"
    And I should see "Fatal Bazooka"

  Scenario: A mission's recruits page can be filtered by groups
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I should see "Showing 1 to 4 of 4"
    And I set the field "group" to "Group 1"
    When I press "Apply"
    Then I should see "Showing 1 to 2 of 2"
    And I should see "Fatal Bazooka"
    And I should see "Chris Prolls"
    And I should not see "Athena Novotel"
    And I should not see "David Fontana"
    And I set the field "group" to "Group 2"
    And I press "Apply"
    And I should see "Showing 1 to 2 of 2"
    And I should see "Athena Novotel"
    And I should see "David Fontana"
    And I should not see "Fatal Bazooka"
    And I should not see "Chris Prolls"
    And I set the field "group" to "All participants"
    And I press "Apply"
    And I should see "Showing 1 to 4 of 4"

  Scenario: A mission's recruits page can be filtered by groups and term
    Given I am on the "Ultra Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I should see "Showing 1 to 4 of 4"
    And I set the field "group" to "Group 1"
    And I set the field "term" to "f b"
    When I press "Apply"
    Then I should see "Showing 1 to 1 of 1"
    And I should see "Fatal Bazooka"
