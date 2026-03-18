@block @block_gearup
Feature: Testing choosing a voice for the quest
  In order for students to listen to the narrator
  As a teacher
  I need to choose a voice

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And I am on the "c1" "Course" page logged in as "t1"
    And I click on "Manage" "link" in the "Level Up Quest" "block"

# Voice can be set during the wizard 1st step
# It can be changed during after the wizard 1st step
# It can be set to nothing
# It can be set to another language
# It can be edited from the quest page, changed to nothing, back to something.

  @javascript
  Scenario: Teachers choose a voice in the wizard
    Given I click on "Quest" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Quest"
    And I select a visual
    And I set the field "Language" to "French (France)"
    And I set the field "Voice" to "Fred 👨 (fr-FR)"
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I set the field "Display as" to "Objective 1"
    And I press "Save changes"
    And I press "Continue"
    And I press "Skip"
    And I press "Continue"
    And I press "Continue"
    And I should see "You're done!"
    And I follow "View quest"
    When I click on "Edit" "button" in the definition "Identity"
    Then the field "Language" in the "Edit quest" "dialogue" matches value "French (France)"
    And the field "Voice" in the "Edit quest" "dialogue" matches value "Fred 👨 (fr-FR)"

  @javascript
  Scenario: Teachers choose a voice in the wizard and go back to change it
    Given I click on "Quest" "link" in the "#region-main" "css_element"
    And I set the field "title" to "The First Quest"
    And I select a visual
    And I set the field "Language" to "French (France)"
    And I set the field "Voice" to "Fred 👨 (fr-FR)"
    And I press "Continue"
    And I press "New objective"
    And I click on "Manual" clickable element
    And I set the field "Display as" to "Objective 1"
    And I press "Save changes"
    And I press "Continue"
    When I follow "Identity"
    And I set the field "Language" to "Italian (Italy)"
    And I set the field "Voice" to "Maria 👩 (it-IT)"
    And I press "Continue"
    And I press "Continue"
    And I press "Skip"
    And I press "Continue"
    And I press "Continue"
    And I should see "You're done!"
    And I follow "View quest"
    And I click on "Edit" "button" in the definition "Identity"
    Then the field "Language" in the "Edit quest" "dialogue" matches value "Italian (Italy)"
    And the field "Voice" in the "Edit quest" "dialogue" matches value "Maria 👩 (it-IT)"

  @javascript
  Scenario: Teachers choose a voice in the quest identity
    Given the following "block_gearup > quests" exist:
      | title       | course | instructions              | startmode |
      | First Quest | c1     | The instructions are here | 1         |
    And I am on the "First Quest" "block_gearup > quest" page logged in as "t1"

    And I click on "Edit" "button" in the definition "Identity"
    And the field "Language" in the "Edit quest" "dialogue" matches value "--"
    And I set the field "Language" in the "Edit quest" "dialogue" to "French (France)"
    And I set the field "Voice" in the "Edit quest" "dialogue" to "Fred 👨 (fr-FR)"
    When I click on "Save changes" "button" in the "Edit quest" "dialogue"

    And I click on "Edit" "button" in the definition "Identity"
    Then the field "Language" in the "Edit quest" "dialogue" matches value "French (France)"
    And the field "Voice" in the "Edit quest" "dialogue" matches value "Fred 👨 (fr-FR)"
    And I set the field "Voice" in the "Edit quest" "dialogue" to "Maria 👩 (fr-FR)"
    And I click on "Save changes" "button" in the "Edit quest" "dialogue"

    And I click on "Edit" "button" in the definition "Identity"
    Then the field "Language" in the "Edit quest" "dialogue" matches value "French (France)"
    And the field "Voice" in the "Edit quest" "dialogue" matches value "Maria 👩 (fr-FR)"
    And I set the field "Language" in the "Edit quest" "dialogue" to "German (Germany)"
    And I set the field "Voice" in the "Edit quest" "dialogue" to "Hans 👨 (de-DE)"
    And I click on "Save changes" "button" in the "Edit quest" "dialogue"

    And I click on "Edit" "button" in the definition "Identity"
    And the field "Language" in the "Edit quest" "dialogue" matches value "German (Germany)"
    And the field "Voice" in the "Edit quest" "dialogue" matches value "Hans 👨 (de-DE)"
    And I set the field "Language" in the "Edit quest" "dialogue" to "--"
    And I click on "Save changes" "button" in the "Edit quest" "dialogue"

    And I click on "Edit" "button" in the definition "Identity"
    And the field "Language" in the "Edit quest" "dialogue" matches value "--"
