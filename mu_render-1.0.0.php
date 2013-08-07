<?php

/*

This the Mu_Render php file.

It is used to preprocess a txt file into a html file.
The website and the documentation are on
pierre.gaudichon.free.fr/Mu_Render

Created by Pierre Gaudichon on august 2013
Feel free to use in your own website.

mail : Umbra.sf@gmail.com
site : pierre.gaudichon.free.fr/Mu_Render

*/

/*
Copyright (c) 2013, Pierre Gaudichon (France)
All rights reserved.
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright
  notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.
* Neither the name of the University of California, Berkeley nor the
  names of its contributors may be used to endorse or promote products
  derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/






function mu_render_el($elStr){

    $nbIndent = 0;
    $tagName = "";
    $prop = array();
    $returnText = "";


    //On vire l'indentation et on garde le nombre d'indentation
    while(ord($elStr[0]) == 9){
        $nbIndent++;
        $elStr = substr($elStr, 1, strlen($elStr));
        }

    trim($elStr);

    //On récupere le tag name est on le vire de la chaine
        //Si le tagName et d'autres trucs
        if(strpos($elStr, " ")){$tagName = substr($elStr, 0, strpos($elStr, " "));}
        else{$tagName = substr($elStr, 0, strlen($elStr));}
        $elStr = trim(substr($elStr, strlen($tagName), strlen($elStr)-strlen($tagName)));


    //Si pas un textNode et des attributs
    if($tagName != "tx" AND $tagName != "doctype" AND strlen($elStr)!=0){
        $coupleArr = explode(",", $elStr);

        foreach ($coupleArr as $key => $value) {

            $value = trim($value);
            $coupleArr[$key] = explode("=", $value);            

            //Class
            if(strpos(trim($coupleArr[$key][0]), ".") === 0){
                $prop["class"] = substr($coupleArr[$key][0], 1, strlen($coupleArr[$key][0]));
                }

            //Id
            else if(strpos(trim($coupleArr[$key][0]), "#") === 0){
                $prop["id"] = substr($coupleArr[$key][0], 1, strlen($coupleArr[$key][0]));
                }

            //Attribut avec valeur
            else if( isset($coupleArr[$key][1])){
                $prop[trim($coupleArr[$key][0])] = trim($coupleArr[$key][1]);
                }

            //Attribut sans valeur
            else{
                $prop[trim($coupleArr[$key][0])] = "";
                }
            }

        $returnText = $returnText."<".$tagName." ";
        foreach ($prop as $key => $value) {
            if($value != ""){$returnText = $returnText.$key."=\"".$value."\""." ";}
            else{$returnText = $returnText.$key." ";}
            }
        $returnText = $returnText.">";
        }

    //Si tagName et c'est tout
    else if($tagName != "tx" AND $tagName != "doctype" AND strlen($elStr)==0){
        $returnText = "<".$tagName.">";
        }

    //Si un textNode
    else if($tagName == "tx"){
        $returnText = $elStr;
        }

    //Si le doctype
    else if($tagName == "doctype"){
        $returnText = "<!DOCTYPE html>";
        }

    return array(
        "type" => $tagName,
        "text" => $returnText,
        "indent" => $nbIndent
        );
    }

function mu_render_endTag($str, $tag){

    $listOrphanTagName = array(
        "doctype","area","base","basefont",
        "br","embed","frame","hr",
        "img","input","link","meta",
        "nextid","option","tbody","td",
        "tfoot","th","tr"
        );

    if($tag != "tx" AND array_search($tag, $listOrphanTagName) === false){$str = $str."</".$tag.">";}
    //$str = $str."\n";
    return $str;
    }

function mu_get_processed_file($path){
    if(file_exists($path)){
        ob_start();
        include($path);    
        return ob_get_clean();
        }
    else{
        return $path." Not Found.";
        }
    
    }

function mu_render($url){    

    $a = explode(chr(10), mu_get_processed_file($url));
    $domArr = array();
    $varsName = array();
    $varsValue = array();

    foreach ($a as $key => $value) {
        $_number = 0;

        //Delete comments
            if(strpos($value, "//") !== false){
                $a[$key] = substr($value, 0, strpos($value, "//"));
                }
            /*$letters = str_split($a[$key]);

            foreach ($letters as $key2 => $value2) {
                if(ord($letters[$key2]) == 47 AND ord($letters[$key2+1]) == 47){ //Si char est "/" et le suivant est "/"
                    $_number = $key2;
                    }
                }
            if($_number != 0){
                $a[$key] = substr($a[$key], 0, $_number);
                $_number = 0;
                }*/

        //Delete space at the end of a line
            $a[$key] = rtrim($a[$key]);

        //Creation du tableau des variables
            if(strpos(trim($a[$key]), "@") === 0){
                $_var = trim($a[$key]);

                $_varName = trim(substr($_var, 1, strpos($_var, "=")-1));
                $_varVal = trim(substr($_var, strpos($_var, "=")+1, strlen($_var)-strpos($_var, "=")));

                array_push($varsName, $_varName);
                array_push($varsValue, $_varVal);

                $a[$key] = "";
                }

        }

        //Remplacement des url par leurs valeurs
        foreach ($varsValue as $key => $value) {
            if(strpos($value, "url(") === 0 and strpos($value, ")") === strlen($value)-1){
                $varsValue[$key] = mu_get_processed_file(trim(substr($value, 4, strlen($value)-5)));
                }
            }


        foreach ($a as $key => $value) {
            //Si une ligne pas vide on remplis la ligne avec l'el correspondant
            if(strlen($a[$key])>0){
                array_push($domArr, mu_render_el($a[$key]));
                }
            }
        
        

    $actualIndent = 0;
    $lastTagName = array();
    $str = "";

    foreach ($domArr as $key => $value) {

        $str = $str.$value["text"];

        array_push($lastTagName, $value["type"]); //Pour le dernier element

        //Si on est avant la derniere ligne
        if(isset($domArr[$key+1])){

            array_pop($lastTagName); //pour le dernier element

            $valuePlus = $domArr[$key+1];

            //Si indentation plus forte apres
            if($valuePlus["indent"] > $value["indent"]){
                //On stoque le tagName dans le tableau $lastTagName
                array_push($lastTagName, $value["type"]);
                }

            //Si indentation égale ou moins forte apres
            else if($valuePlus["indent"] == $value["indent"]){
                //On ferme la balise
                $str = mu_render_endTag($str, $value["type"]);
                }

            //Si indentation moins forte apres
            else if($valuePlus["indent"] < $value["indent"]){

                //On ferme la balise
                $str = mu_render_endTag($str, $value["type"]);

                $diffIndent = $value["indent"]-$valuePlus["indent"];

                //on ferme la derniere balise stoquée
                for($i = 0; $i < $diffIndent; $i++){
                    $str = mu_render_endTag($str, end($lastTagName));
                    //On retire la derniere balise du tableau $lastTagName
                    array_pop($lastTagName);
                    }
                }
            }   
        }

        //Pour la derniere ligne
        while(count($lastTagName) != 0){
            $str = mu_render_endTag($str, end($lastTagName));
            array_pop($lastTagName);
            }

        //Replacement des var par leurs valeur
        foreach ($varsName as $key => $value) {
            if(strpos($value, "\@".$value) === false){
                $str = str_replace("@".$value, $varsValue[$key], $str);
                }
            }


        echo $str;
        //print_r($varsValue);

    };
?>