"use strict";
/*
Created 27/09/2026 By Roger Williams

Javascript primarily for tracking pages opened by user 
as well as storing other values

*/

//for page history
export let dictPages = {};

//global var for current page
export let pgeCurrent = null;

//used when page loads to set header document
let docHeader = null;


export function SetHeader(objValue)
{
    docHeader = objValue;
    alert(docHeader);
}

export function GetHeader() {
    return docHeader;
}

