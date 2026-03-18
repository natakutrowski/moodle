@block @block_gearup
Feature: Testing completing a challenge
  In order for students to complete challenges
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
    And the following "block_gearup > challenges" exist:
      | title           | course |
      | First Challenge | c1     |
    And the following "block_gearup > recruits" exist:
      | mission         | user  |
      | First Challenge | s1    |

  @javascript
  Scenario: Students can complete challenges
    Given I am on the "c1" "Course" page logged in as "s1"
    And I should see "Course challenges"
    And I should see "First Challenge"
    And I click on "First Challenge" "link" in the "Level Up Quest" "block"
    And I should not see "Completed" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"

    And I am on the "First Challenge" "block_gearup > challenge recruits" page logged in as "t1"
    And I follow "Student One"
    And I choose the "View" item in the "Menu" action menu
    And I press "Increment"
    When I press "Confirm"

    And I am on the "c1" "Course" page logged in as "s1"
    And I click on "First Challenge" "link" in the "Level Up Quest" "block"
    Then I should see "Completed" in the ".modal" "css_element"
