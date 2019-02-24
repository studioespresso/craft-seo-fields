import CodeMirror from 'Codemirror';

var robots = document.getElementById('robots');
var editor = CodeMirror.fromTextArea(robots, {
    lineNumbers: true,
    matchBrackets: false,
    mode: 'twig',
    indentUnit: 4,
    viewportMargin: 8
});