unit uRC4;
{$HINTS OFF}

interface

uses Windows;
function RC4(Expression, Password:string):string;

type
  TByteArray = array of Byte;

implementation

procedure Move(Destination, Source: Pointer; dLength:Cardinal);
begin
  CopyMemory(Destination, Source, dLength);
end;

function RC4(Expression, Password:string):string;
var
  RB:         array[0..255] of integer;
  X, Y, Z:    LongInt;
  Key:        TByteArray;
  ByteArray:  TByteArray;
  Temp:       Byte;
begin
  if Length(Password) = 0 then
    Exit;
  if Length(Expression) = 0 then
    Exit;
  if Length(Password) > 256 then
  begin
    SetLength(Key, 256);
    Move(@Key[0], @Password[1], 256)
  end
  else
  begin
    SetLength(Key, Length(Password));
    Move(@Key[0], @Password[1], Length(Password));
  end;
  for X := 0 to 255 do
    RB[X] := X;
  X := 0;
  Y := 0;
  Z := 0;
  for X := 0 to 255 do
  begin
    Y := (Y + RB[X] + Key[X mod Length(Password)]) mod 256;
    Temp := RB[X];
    RB[X] := RB[Y];
    RB[Y] := Temp;
  end;
  X := 0;
  Y := 0;
  Z := 0;
  SetLength(ByteArray, Length(Expression));
  Move(@ByteArray[0], @Expression[1], Length(Expression));
  for X := 0 to Length(Expression) - 1 do
  begin
    Y := (Y + 1) mod 256;
    Z := (Z + RB[Y]) mod 256;
    Temp := RB[Y];
    RB[Y] := RB[Z];
    RB[Z] := Temp;
    ByteArray[X] := ByteArray[X] xor (RB[(RB[Y] + RB[Z]) mod 256]);
  end;
  SetLength(Result, Length(Expression));
  Move(@Result[1], @ByteArray[0], Length(Expression));
end;

{$HINTS ON}

end.