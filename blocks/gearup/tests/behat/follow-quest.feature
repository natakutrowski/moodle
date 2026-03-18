@block @block_gearup
Feature: Testing following a quest
  In order for students to follow quests
  As a teacher
  I need to set them up

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And the following "block_gearup > quests" exist:
      | title       | course | instructions              | startmode |
      | First Quest | c1     | The instructions are here | 1         |
    And the following "block_gearup > recruits" exist:
      | mission     | user  |
      | First Quest | s1    |

  @javascript
  Scenario: Students can opt-in to take on optional quests
    Given I am on the "c1" "Course" page logged in as "s1"
    And I should see "Available course quests"
    And I should see "First Quest"
    And I click on "First Quest" "link" in the "Level Up Quest" "block"
    When I click on "Accept" "button" in the ".modal" "css_element"
    Then I should see "Objective 1"
    And I should see "The instructions are here"
    And I click on "OK" "button" in the ".modal" "css_element"
    And I should see "Ongoing course quests"

    And I am on the "First Quest" "block_gearup > quest recruits" page logged in as "t1"
    And I follow "Student One"
    And I press "Increment"
    And I press "Confirm"

    And I am on the "c1" "Course" page logged in as "s1"
    And ".block_gearup [data-attention]" "css_element" should exist
    And I click on "First Quest" "link" in the "Level Up Quest" "block"
    And I press "Thank you"
    And I should not see "First Quest" in the "Level Up Quest" "block"
    And I should see "You completed all your quests."
