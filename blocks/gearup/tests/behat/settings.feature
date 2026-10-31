@block @block_gearup
Feature: Configuring Quest before activation
  In order to prepare Quest before activating it
  As an administrator
  I can view and save its settings

  Scenario: Settings have defaults and can be saved before activation
    Given I log in as "admin"
    When I navigate to "Plugins > Blocks > Level Up Quest" in site administration
    Then the field "Keep block drawer open" matches value "0"
    And the field "Ongoing quest limit" matches value "3"
    And the field "Ongoing quest order" matches value "0"
    And the field "Highlighted achievement limit" matches value "3"
    And I set the following fields to these values:
      | Keep block drawer open        | 1            |
      | Ongoing quest limit           | 5            |
      | Ongoing quest order           | Alphabetical |
      | Highlighted achievement limit | 10           |
    And I press "Save changes"
    And I reload the page
    And the field "Keep block drawer open" matches value "1"
    And the field "Ongoing quest limit" matches value "5"
    And the field "Ongoing quest order" matches value "2"
    And the field "Highlighted achievement limit" matches value "10"
