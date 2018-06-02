unit PhpCrypt;
{$I MSender.Inc}

interface

  function visualEncrypt(const AData: string): string;
  function MakeChunk(const AData, AHash: string): string;

implementation

uses
  SysUtils, Windows, RxStrUtils;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function visualEncrypt(const AData: string): string;
var
  i: Integer;
begin
  Result := AData;

  for i := 2 to Length(Result) do
    Result[i] := Char(Ord(Result[i]) xor Ord(Result[i - 1]));
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function rnd_string(ALen: Integer): string;
var
  i, c: Integer;
begin
  Result := '';

  for i := 0 to ALen - 1  do
  begin
    c := Random(255);
    if c = 0 then
       Inc(c);
    Result := Result + Char(c);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetBinHash(const AHash: string): string;
var
  i, j: Integer;
begin
  Result := '';

  i := 1;
  while i < Length(AHash) do begin
    j := StrToInt('$' + Copy(AHash, i, 2));
    Result := Result + Chr(j);
    Inc(i, 2);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function MakeChunk(const AData, AHash: string): string;
var
  data_size: DWORD;
begin
  Result := rnd_string(20) + MakeStr(#0, 48);
  data_size := Length(AData) + 48;

  PDWORD(@Result[21])^ := data_size;
  //data_size := 0;
  //data_size := PDWORD(@Result[21])^;

  Result := Result + GetBinHash(AHash) + AData;
end;


initialization
  Randomize;

end.
