// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script interface between server side code and client size moodle.js
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2017 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.qtype_speechace = M.qtype_speechace || {};

M.qtype_speechace.init = function() {};

M.qtype_speechace.attachMoodleViewController = function(Y,opts) {
	var serverOpts = {
		updatecontrol: opts.updatecontrol,
		contextid: opts.contextid,
		component: opts.component,
		filearea: opts.filearea,
		itemid: opts.itemid,
		baseurl: opts.baseurl,
		requestid: opts.requestid,
		slot: opts.slot,
		usageid: opts.usageid,
		experthash: opts.experthash,
		sesskey: opts.sesskey
	};
	var question = {
		id: opts.id,
		opts: serverOpts,
        dialect: opts.dialect
	};
	if (opts.text) {
		question['text'] = opts.text;
	} else {
		question['word'] = opts.word;
	}
	if (opts.image) {
	    question['image'] = opts.image;
    }
    var scoreMessages={
	    textOne: opts.scoremessagesTextOne,
        textTwo: opts.scoremessagesTextTwo,
        textThree: opts.scoremessagesTextThree,
    };
    var options = {
		question: question,
        workerPath: opts.workerPath,
		readOnly: opts.readOnly,
		swfPath: opts.swfPath,
		preferSwf: opts.preferSwf,
        expertAudioPos: opts.expertAudioPos,
        phonemeSymbolType: opts.phonemeSymbolType,
        showExpertAudio: opts.showExpertAudio,
        showNumericScore: opts.showNumericScore,
        scoreMessages: scoreMessages,

    };
	var answerId = undefined;
	if (opts.answerId) {
		answerId = opts.answerId;
	}
    var reactElement = SpeechaceReactJSBinding.createMoodleViewController(
                            options,
                            opts.color, 
                            opts.fillColor, 
                            opts.volumeFillColor,
							answerId);
    var domElement = document.getElementById(opts.domid);
    SpeechaceReactJSBinding.renderDOM(reactElement, domElement);    
};

M.qtype_speechace.attachScoreMessagesViewController = function(Y,opts){

    var parentElementId =  opts.parentElementId;

    var scoreMessagesText = {
        textOneInputName: opts.textOne,
        textTwoInputName: opts.textTwo,
        textThreeInputName: opts.textThree,
    };

    var scoreMessagesSelect ={
        selectOneInputName: opts.selectOne,
        selectTwoInputName: opts.selectTwo
    }

    var scoreMessages_resetValues={
        textOne_default: opts.textOne_default,
        textTwo_default: opts.textTwo_default,
        textThree_default: opts.textThree_default,
        selectOne_default: opts.selectOne_default,
        selectTwo_default: opts.selectTwo_default,
    }

    var scoreMessagesInfo={
        text: scoreMessagesText,
        select: scoreMessagesSelect,
        parentElementId: parentElementId,
        resetValues:  scoreMessages_resetValues
    }

    var reactElement = SpeechaceReactJSBinding.createScoreMessagesViewController(
        scoreMessagesInfo
    );

    var domElement = document.getElementById(opts.elementId);
    SpeechaceReactJSBinding.renderDOM(reactElement, domElement);

};

M.qtype_speechace.attachMoodleEditViewController = function(Y,opts) {
    var serverOpts = {
        baseurl: opts.baseurl,
        contextid: opts.contextid,
        sesskey: opts.sesskey,
        questionid: opts.questionid,
        moodleitemid: opts.moodleitemid,
    };
    var question = {
        id: opts.id,
        opts: serverOpts,
        viewId: opts.viewId,
        textInputName: opts.text,
        sourcetypeInputName: opts.sourcetype,
        moodleitemidInputName: opts.itemid,
        speechacekeyInputName: opts.speechacekey,
        moodlekeyInputName: opts.moodlekey,
        dialectInputName: opts.dialectElementId,

    };
    var options = {
        question: question,
        workerPath: opts.workerPath,
        readOnly: opts.readOnly,
        swfPath: opts.swfPath,
        preferSwf: opts.preferSwf,
    };


    var reactElement = SpeechaceReactJSBinding.createMoodleEditViewController(
        options,
        opts.color,
        opts.fillColor,
        opts.volumeFillColor
        );
    var domElement = document.getElementById(opts.viewId);
    SpeechaceReactJSBinding.renderDOM(reactElement, domElement);
};

