@block @block_gearup @javascript
Feature: Mission action menus
  In order to manage missions
  As a teacher
  I can edit, preview and manage instances through action menus

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user | course | role           |
      | t1   | c1     | editingteacher |

  Scenario Outline: Teachers can edit mission identities from action menus
    Given the following "block_gearup > missions" exist:
      | title        | course | type   | visual   |
      | Menu mission | c1     | <type> | <visual> |
    And I am on the "Menu mission" "block_gearup > mission" page logged in as "t1"
    When I click on "Actions" "button" in the definition "<section>"
    And I click on "Edit" "button" in the definition "<section>"
    And I set the field "Name" in the "<dialogue>" "dialogue" to "Renamed mission"
    And I click on "Save changes" "button" in the "<dialogue>" "dialogue"
    Then I should see "Renamed mission"
    And I reload the page
    And I should see "Renamed mission"

    Examples:
      | type                                   | visual   | section  | dialogue         |
      | block_gearup\local\mission\quest        | person-1 | Identity | Edit quest       |
      | block_gearup\local\mission\achievement  | badge-1  | Identity | Edit achievement |
      | block_gearup\local\mission\challenge    |          | Name     | Edit challenge   |
      | block_gearup\local\mission\streak       |          | Name     | Edit             |

  Scenario Outline: Teachers can edit and preview each part of a quest
    Given the following "block_gearup > quests" exist:
      | title      | course | startmode | description       | instructions       | feedback       |
      | Menu quest | c1     | 1         | Introduction text | Instructions text  | Closing text   |
    And I am on the "Menu quest" "block_gearup > mission" page logged in as "t1"
    When I click on "Actions" "button" in the definition "<section>"
    Then "Edit" "button" should appear before "Preview" "button" in the ".dropdown-menu.show" "css_element"
    And I click on "Edit" "button" in the definition "<section>"
    And I set the field "<field>" in the "Edit quest" "dialogue" to "Updated story"
    And I click on "Save changes" "button" in the "Edit quest" "dialogue"
    And I click on "Actions" "button" in the definition "<section>"
    And I click on "Preview" "button" in the definition "<section>"
    And I should see "Updated story" in the "Preview" "dialogue"
    And I click on "Close" "button" in the "Preview" "dialogue"

    Examples:
      | section                  | field         |
      | Story line: Introduction | Introduction  |
      | Story line: Instructions | Instructions  |
      | Story line: Closing story| Closing story |

  Scenario: Teachers can view and delete a recruited mission instance
    Given the following "users" exist:
      | username | firstname | lastname |
      | s1       | Student   | One      |
    And the following "course enrolments" exist:
      | user | course | role    |
      | s1   | c1     | student |
    And the following "block_gearup > streaks" exist:
      | title      | course |
      | Menu quest | c1     |
    And the following "block_gearup > recruits" exist:
      | mission    | user |
      | Menu quest | s1   |
    And I am on the "Menu quest" "block_gearup > streak recruits" page logged in as "t1"
    And I follow "Student One"
    When I open the action menu in ".flexible" "css_element"
    And I click on "View" "link" in the ".flexible" "css_element"
    Then I should see "Menu quest"
    And I should see "Student One"
    And "Increment" "button" should exist
    And I am on the "Menu quest" "block_gearup > streak recruits" page
    And I follow "Student One"
    And I open the action menu in ".flexible" "css_element"
    And I click on "Delete" "link" in the ".flexible" "css_element"
    And I click on "Delete" "button" in the "Student One" "dialogue"
    And I should see "The mission instance has been deleted."
    And I should see "No missions"
    And I reload the page
    And I should see "No missions"
