@block @block_gearup
Feature: Testing recruiting users
  In order to give missions to my students
  As a teacher
  I must recruit them to their missions

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
      | s2       | Student   | Two      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
      | s2       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |

  @javascript
  Scenario: Teachers can recruit users for quests
    Given the following "block_gearup > quests" exist:
      | title        | course |
      | First Quest  | c1     |
    And I am on the "First Quest" "block_gearup > quest recruits" page logged in as "t1"
    And I should see "No recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    When I press "Save changes"
    Then I should see "Student One"
    And I should not see "Student Two"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student Two"
    And I press "Save changes"
    And I should see "Student One"
    And I should see "Student Two"

  @javascript
  Scenario: Teachers can recruit users for achievements
    Given the following "block_gearup > achievements" exist:
      | title             | course |
      | First Achievement | c1     |
    And I am on the "First Achievement" "block_gearup > achievement recruits" page logged in as "t1"
    And I should see "No recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    When I press "Save changes"
    Then I should see "Student One"
    And I should not see "Student Two"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student Two"
    And I press "Save changes"
    And I should see "Student One"
    And I should see "Student Two"

  @javascript
  Scenario: Teachers can recruit users for challenges
    Given the following "block_gearup > challenges" exist:
      | title           | course |
      | First Challenge | c1     |
    And I am on the "First Challenge" "block_gearup > challenge recruits" page logged in as "t1"
    And I should see "No recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    When I press "Save changes"
    Then I should see "Student One"
    And I should not see "Student Two"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student Two"
    And I press "Save changes"
    And I should see "Student One"
    And I should see "Student Two"

  @javascript
  Scenario: Teachers can recruit users for streaks
    Given the following "block_gearup > streaks" exist:
      | title        | course |
      | First Streak | c1     |
    And I am on the "First Streak" "block_gearup > streak recruits" page logged in as "t1"
    And I should see "No recruits"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student One"
    When I press "Save changes"
    Then I should see "Student One"
    And I should not see "Student Two"
    And I press "Recruit users"
    And I set the field "Users to recruit" to "Student Two"
    And I press "Save changes"
    And I should see "Student One"
    And I should see "Student Two"
