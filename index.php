<?php
/**
 * Summary.
 *
 * Das Programm habe ich entwickelt, um das Pi-Puzzle von getdigital.de (https://www.getdigital.de/pages/offlineprodukt/pi-puzzle),
 * das ich vor Jahren mal von Freunden zum Geburtstag bekam, nach Ziffernsequenzen durchsuchen zu können, weil sich das meiner Meinung
 * nach durch die konstante Schriftgröße und gleicher Zeilenlänge einfach anbietet. 
 * 
 * Basis war dabei eine Textdatei mit der ersten Million Stellen von Pi, welche ich mir für ein viel früheres Projekt aus öffentlich 
 * zugänglichen Quellen für Pi-Nachkommastellen erstellt hatte. Beim Puzzeln des Randes hatte ich meinen Million-Stellen-Einzeiler 
 * mit einem Texteditor gekürzt und die im Puzzle gefundenen Zeilenumbrüche dort nachgebaut. Am Ende war es ein großer, durchsuchbarer 
 * rechteckiger Block aus Ziffern analog zum Puzzle. 
 *  
 * Kernstück meines Programms ist die Klasse "Trimmer", welche diesen rechteckigen Textblock, einliest und in rechteckige Blöcke 
 * weiter teilt. Die Aufteilung wird mit float-Werten für Offset und Schrittweite (float) sowohl in x-Richtung (Zeichen) als auch 
 * in y-Richtung (Zeilen) festgelegt. Die Blöcke werden in einem neuen zweidimensionalen Array $grid gelagert, wobei dessen Elemente 
 * eine rechteckige Teilmenge zusammen mit den jeweiligen Koordinaten (der Position) im Gesamtbild enthalten. So lässt sich grob zuordnen,
 * welche Stellen von Pi auf den einzelnen Puzzleteilen stehen. Das machte das Puzzle für mich als Hobby-Programmierer interessant.
 * 
 * @link   https://projekte.svenwachsmuth.de/pi-puzzle-helper/ 
 * @author Sven Wachsmuth
 * @date   02.10.2024
 * @since  1.0.0
 */

echo
<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PI-Puzzle-Helper | svenwachsmuth.de</title>
</head>
<style type="text/css">
  span {
    background-color:#ee0;
  }
</style>
<body>
HTML;

/* Die Klasse "Trimmer", das Herzstück des Programms */
class Trimmer {

  private string $filename;
  private array $src_data, $grid;
  private float $x_offset, $y_offset, $x_stepsize, $y_stepsize;
  private int $grid_rows, $grid_cols;

  /* legt die grundlegenden Parameter fest: welche Datei geladen und wie die Aufteilung erfolgen soll. */ 
  public function __construct( string $filename, float $x_offset, float $x_stepsize, float $y_offset, float $y_stepsize ) {
    $this->filename = $filename;
    $this->x_offset = $x_offset;
    $this->x_stepsize = $x_stepsize;
    $this->y_offset = $y_offset;
    $this->y_stepsize = $y_stepsize;
    $this->grid = [];
    $this->grid_rows = 0;
    $this->grid_cols = 0;
    $this->src_data = @file($this->filename);
    $this->_fill_grid();    
  }

  /* Teilt die Textzeile mit Index $row horizontal in Stücke auf und liefert diese als Array zurück. */
  private function _cut_one_row( int $row ) : array {
    $src = trim($this->src_data[ $row ], " \n\r\t\v\x00");
    $src_length = strlen($src);
    $out = [];
    $cursor = 0;
    $offset = 0; $length = 0;
    while( $cursor < $src_length ) {
      $offset = (int)round($cursor);
      $cursor += $cursor > 0 ? $this->x_stepsize : $this->x_offset;
      $length = (int)round( $cursor - $offset );
      $out[] = substr( $src, $offset, $length);  
    }
    return $out;
  }
  
  /* Teilt einen Block aus gleich langen Zeichenfolgen zeile für Zeile horizontal auf und füllt mit den Stücken das $grid auf. */ 
  private function _fill_grid() {
    $row = 0;
    $row_count = count($this->src_data);
    $break_row = -1;
    $grid_row = -1;
    while( $row < $row_count ){
      $data_row = $this->_cut_one_row( $row );
      if( $row > round($break_row) ) {
        $this->grid[] = $data_row;
        $grid_row = count( $this->grid) - 1; 
        $break_row += $break_row > 0 ? $this->y_stepsize : $this->y_offset;
      } else {
        for( $grid_col = 0; $grid_col < count($data_row); $grid_col++ ) {
          $this->grid[ $grid_row ][ $grid_col ] .= '<br/>'.$data_row[ $grid_col ];
        }
      }
      $row ++;
    }
    $this->grid_rows = count($this->grid);
    $this->grid_cols = count($this->grid[0]);
  }
  
  /* erzeugt für ein bestimmtes Element $grid_row, $grid_col aus dem $grid-Array HTML und gibt das zurück. 
  *  Wenn highlight_start > -1 ist, werden Ziffernbereiche dabei mit einer Hintergrundfarbe markiert. */
  private function _grid_element( int $grid_row, int $grid_col, int $highlight_start = -1, int $highlight_length = 1 ) : string {
    $x_rand = $grid_col == 0 || $grid_col == $this->grid_cols - 1;
    $y_rand = $grid_row == 0 || $grid_row == $this->grid_rows - 1;
    $typ = ( $x_rand || $y_rand ) 
    ? ( $x_rand && $y_rand ? 'Ecke' : 'Kante')
    : '';
    $element_content = $this->grid[$grid_row][$grid_col];
    return 
    '<p><b>'.($grid_row+1).' | '.($grid_col+1).'</b> <i>'.$typ.'</i><br/>'.
    ( ( $highlight_start == -1 )
    ? $element_content
    : substr( $element_content, 0, $highlight_start ) . 
      '<span>' . substr( $element_content, $highlight_start, $highlight_length ) . '</span>' . 
      substr( $element_content, $highlight_start + $highlight_length ) ).
    '</p>';
  }

  /* erzeugt eine rechteckige Ansicht aller Elemente im $grid und liefert dieses als HTML zurück. */
  public function render_grid() : string {
    $element_width = 120;
    $element_height = 135;
    $width_gesamt_px = 'width:'.($element_width * $this->grid_cols).'px;';
    $height_gesamt_px = 'height:'.($element_height * $this->grid_rows).'px;';
    $html =
    <<<HTML
    <div style="$width_gesamt_px $height_gesamt_px position:relative;">
    HTML;
    for( $i = 0; $i < $this->grid_cols * $this->grid_rows; $i++) {
      $col = $i % $this->grid_cols;
      $row = (int)floor($i / $this->grid_cols);
      $html .=
      '<div style="position:absolute; top:'.$row * $element_height.'px; left: '.$col * $element_width.'px; width:'.$element_width.'px;height:'.$element_height.'.px;">'.
      $this->_grid_element( $row, $col ).
      '</div>';
    }
    $html .=
    <<<HTML
    </div>
    HTML;
    return $html;
  }

  /* sucht eine Ziffernfolge in einem $grid-Element und gibt bei Erfolg HTML und wenn nicht false zurück. */
  private function _match( int $row, int $col, string $search ) : string|bool {
    $search_pos = strpos( $this->grid[$row][$col], $search );
    if( $search_pos !== false ) {
      return $this->_grid_element( $row, $col, $search_pos, strlen( $search ) );
    } else {
      return false;
    }
  }

  /* Verarbeitet die Suchanfrage: Jedes Element in der kommaseparierten Liste startet einen kompletten Suchlauf durch alle Elemente. 
  *  Dabei wird ein Array $matches mit Fundstellen gefüllt. Diese werden in der Reihenfolge der Sucheingaben horizontal ausgegeben.
  *  Wenn  für eine Ziffernfolge mehrere Elemente gefunden werden, dann werden diese nach Zeile und Spalte sortiert. */ 
  public function render_filtered_grid( string $search ) : string {
    function cmp($a,$b){
      if ( $a['row'] == $b['row'] ) {
        return
          $a['col'] == $b['col'] 
          ? 0 
          : ( $a['col'] < $b['col'] ? -1 : 1 );
      }
      return 
      ( $a['row'] < $b['row'] ) ? -1 : 1; 
    }
    $element_width = 120;
    $element_height = 135;
    $html = '';
    strtr( $search, [' '=>''] );
    $search_list = explode(',', $search);
    $matches = [];
    foreach( $search_list as $key=>$digits ){
      $matches[ $key ] = [];
      for( $i = 0; $i < $this->grid_cols * $this->grid_rows; $i++) {
        $col = $i % $this->grid_cols;
        $row = (int)floor($i / $this->grid_cols);
        $match = $this->_match( $row, $col, $digits );
        if ( $match ) {
          $matches[ $key ][] = [ 'row'=>$row, 'col'=>$col, 'content'=>$match, 'id'=>$key ];
        }
      }
    }
    for( $i = 0; $i < count($matches); $i++ ) {
      usort( $matches[$i], 'cmp' );
    }
    $html =
    '<div style="width:100%;height:'.($element_height+80).'px;overflow:auto;position:relative;">';
    $offset = 0;
    for( $i = 0; $i < count($matches); $i++ ) {
      if ($offset > 0) $offset += 50;
        $html .= 
        '<div style="position:absolute; top:10px; left: '.$offset.'px;">'.$search_list[$i].':</div>';
      foreach( $matches[$i] as $match ) {
        $html .= 
        '<div style="position:absolute; top:20px; left: '.$offset.'px; width:'.$element_width.'px;height:'.$element_height.'.px;">'.
        $match['content'].
        '</div>';
        $offset += $element_width;
      }
      if( count($matches[$i]) == 0 ) {
        $offset += $element_width;
      }
  }
    $html .=
    <<<HTML
    </div>
    HTML;
    return $html;
  }

}

/* Eine Instanz von "Trimmer" mit den aus dem Puzzle selbst ermittelten Werten für Offset und Schrittweite jeweils in x- und y-Richtung. */ 
$TR = new Trimmer( 'puzzle.txt', 9.33, 12.8, 5, 6 );
echo
<<<HTML
<p style="color:#030;">
// PI-Puzzle-Helper 1.0 - inoffizielles Such-Tool für das <a href="https://www.getdigital.de/pages/offlineprodukt/pi-puzzle">Pi-Puzzle von getdigital.de</a><br/>
// In PHP geschrieben von Sven Wachsmuth "aus der Not heraus ;-)"<br/>
// Anwendung: Je 5-6 zusammenhängende Stellen aus der Mitte des Puzzleteils mit Komma getrennt<br/> 
// eingeben und auf den Knopf rechts drücken. Im Ergebnis stehr über jedem Teil die Position (Reihe | Spalte)<br/>
// Ein leeres Suchfeld zeigt das gesamte Puzzleteile als Zahlenblöcke an. Hier kann man im Browser mit Strg+F suchen.<br/> 
</p> 
HTML;

/* Wenn es einen Parameter "s" in der URL weise ihn der Variable $search zu, ansonsten ist $search = '' */
$search = trim( $_GET['s'] ?? '' ); 

/* Schreibe das HTML für das Such-Formular */
echo
<<<HTML
<form id="searchform" method="get" action="index.php">
  <input type="text" id="s" name="s" placeholder="Ziffernfolgen mit , getrennt..." style="width:80%;" value="$search" />
  <input type="submit" id="subit" value="Suche" />
</form>
HTML;

/* wenn das Textfeld leer ist ... */
echo ($search == '' )
? $TR->render_grid()                     // ... zeige alles an
: $TR->render_filtered_grid( $search );  // ... zeige nur die Teile mit Suchergebnissen an. 

?>
</body>
</html>