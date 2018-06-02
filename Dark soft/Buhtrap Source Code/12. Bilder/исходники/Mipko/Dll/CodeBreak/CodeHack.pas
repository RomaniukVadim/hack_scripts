unit CodeHack;

interface

 function SetCodeHook(ProcAddress, NewProcAddress: Pointer;
                      var NextProcAddress: Pointer): Boolean;

 function SetCodeHook4(ProcAddress, NewProcAddress: Pointer;
                       var NextProcAddress: Pointer): Boolean;

 procedure Poke(Addr: Pointer; Value: Byte; Offset: Integer = 0);                      

implementation

uses
  Windows, LDasm;

const
  JMP_SIZE = 5;

type
  PFunctionRestoreData = ^TFunctionRestoreData;
  TFunctionRestoreData = packed record
    Address: Pointer;
    SaveLen: Byte;   // длина сохраненного кода, без JMP_SIZE байт
    SavedCode: array[0..63] of Byte;
 end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure Poke(Addr: Pointer; Value: Byte; Offset: Integer = 0);
begin
  Byte(Pointer(Integer(Addr) + Offset)^) := Value;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Возвращает кол-во байт с заданного адреса, содержащее целое число команд   *}
function GetSafeCommandLen(Proc: Pointer): Cardinal;
var
  pOpcode: ppbyte;
  Length: DWORD;
begin
  Result := 0;
  if Proc = nil then Exit;

  repeat
    Length := SizeOfCode(Proc, @pOpcode);
    Inc(Result, Length);
    if (Result >= JMP_SIZE) then Break;
    Proc := Pointer(DWORD(Proc) + Length);
  until Length = 0;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Выделяет память под структуру для сохранения кода, меняет её атрибуты и
   запоминает адрес в списке                                                  *}
{$HINTS OFF}
function MakeRestoreDataNode: PFunctionRestoreData;
var
  OldProtect: DWORD;
begin
  Result := nil;

  GetMem(Result, SizeOf(TFunctionRestoreData));
  ZeroMemory(Result, SizeOf(TFunctionRestoreData));

  // Делаем память структуры исполняемой
  if not VirtualProtect(Result, SizeOf(TFunctionRestoreData),
                        PAGE_EXECUTE_READWRITE, OldProtect) then
    begin
      Result := nil;
      Exit;
    end;

  // Запоминаем в списке
  //HookList.Add(Result);
end;
{$HINTS ON}

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function SetCodeHook(ProcAddress, NewProcAddress: Pointer;
                     var NextProcAddress: Pointer): Boolean;
var
  OldProtect, JmpValue, Offset, CodeSize: DWORD;
  pRestoreData: PFunctionRestoreData;
begin
  Result := False;

  // Выделяем структуру под сохранение кода
  pRestoreData := MakeRestoreDataNode;
  if pRestoreData = nil then Exit;

  // Получаем длину в байтах целого числа команд
  CodeSize := GetSafeCommandLen(ProcAddress);
  if CodeSize = 0 then Exit;
  //OutPutDebugString(PChar('Code: ' + IntToStr(CodeSize)));

  // Запомнили оригинальный код
  Move(ProcAddress^, pRestoreData^.SavedCode[0], CodeSize);
  pRestoreData^.SaveLen := CodeSize;
  pRestoreData^.Address := ProcAddress;

  // Обрабатываем случай, когда первой командой идет относительный call (opcode E8)
  // необходимо пересчитать адреса в этом случае
  if pRestoreData^.SavedCode[0] = $E8 then
    begin
      Offset   := PDWORD(@pRestoreData^.SavedCode[1])^;
      JmpValue := DWORD(ProcAddress) + Offset + 5;
      Offset   := JmpValue - DWORD(@pRestoreData^.SavedCode[CodeSize]);
      PDWORD(@pRestoreData^.SavedCode[1])^ := Offset;
    end;

  // Дописываем в конец скопированного кода jmp на продолжение ориг.кода
  JmpValue := DWORD(ProcAddress) + CodeSize -
              DWORD(@pRestoreData^.SavedCode[CodeSize]) - JMP_SIZE;
  pRestoreData^.SavedCode[CodeSize] := $E9;
  PDWORD(@pRestoreData^.SavedCode[CodeSize + 1])^ := JmpValue;

  // Пишем адрес трамплина на оригинальную ф-ю
  NextProcAddress := @pRestoreData^.SavedCode[0];

  // Патчим оригинальный код
  if not VirtualProtect(ProcAddress, JMP_SIZE,
                        PAGE_EXECUTE_READWRITE, OldProtect) then Exit;
  JmpValue := DWORD(NewProcAddress) - DWORD(ProcAddress) - JMP_SIZE;
  Byte(ProcAddress^) := $E9;
  DWORD(Pointer(DWORD(ProcAddress) + 1)^) := JMPValue;

  // Возвращаем на место атрибуты страницы памяти
  Result := VirtualProtect(ProcAddress, CodeSize, OldProtect, OldProtect);
end;


{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Копирует тело короткой 4-байтной функции                                                       *}
function SetCodeHook4(ProcAddress, NewProcAddress: Pointer;
                     var NextProcAddress: Pointer): Boolean;
const
  CODE_SIZE = 4;
var
  pRestoreData: PFunctionRestoreData;
begin
  Result := False;

  // Выделяем структуру под сохранение кода
  pRestoreData := MakeRestoreDataNode;
  if pRestoreData = nil then Exit;

  // Запомнили оригинальный код
  Move(ProcAddress^, pRestoreData^.SavedCode[0], CODE_SIZE);
  pRestoreData^.SaveLen := CODE_SIZE;
  pRestoreData^.Address := ProcAddress;

  // Пишем адрес трамплина на оригинальную ф-ю
  NextProcAddress := @pRestoreData^.SavedCode[0];

  // Возвращаем на место атрибуты страницы памяти
  Result := True;
end;

end.
