<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    private $dadosSimulador;
    private $simulacao = [];

    public function simular(Request $request)
    {        
        $this->carregarArquivoDadosSimulador()
             ->simularEmprestimo($request->valor_emprestimo)
             ->filtrarInstituicao($request->instituicoes);
            
        if(count($request->convenios)){
            $this->filtrarConvenio($request->instituicoes, $request->convenios);
        }
        
        if(!is_null($request->parcela)){
            $this->filtrarParcela($request->instituicoes, $request->parcela);    
        }
        return \response()->json($this->simulacao);
    }

    private function carregarArquivoDadosSimulador() : self
    {
        $this->dadosSimulador = json_decode(\File::get(storage_path("app/public/simulador/taxas_instituicoes.json")));
        return $this;
    }

    private function simularEmprestimo(float $valorEmprestimo) : self
    {
        foreach ($this->dadosSimulador as $dados) {
            $this->simulacao[$dados->instituicao][] = [
                "taxa"            => $dados->taxaJuros,
                "parcelas"        => $dados->parcelas,
                "valor_parcela"    => $this->calcularValorDaParcela($valorEmprestimo, $dados->coeficiente),
                "convenio"        => $dados->convenio,
            ];
        }
        return $this;
    }

    private function calcularValorDaParcela(float $valorEmprestimo, float $coeficiente) : float
    {
        return round($valorEmprestimo * $coeficiente, 2);
    }

    private function filtrarInstituicao(array $instituicoes) : self
    {
        if (\count($instituicoes))
        {
            $arrayAux = [];
            foreach ($instituicoes AS $key => $instituicao)
            {
                if (\array_key_exists($instituicao, $this->simulacao))
                {
                     $arrayAux[$instituicao] = $this->simulacao[$instituicao];
                }
            }
            $this->simulacao = $arrayAux;
        }
        return $this;
    }

    private function filtrarConvenio(array $instituicoes, array $convenios) : self
    {
        if($instituicoes == [])
            $instituicoes = ["PAN", "OLE", "BMG"];
        
        if (count($convenios)){
            $arrayAux = [];
            
            $convenios = array_flip($convenios);
            $convenios = array_change_key_case($convenios, CASE_UPPER);
            $convenios = array_flip($convenios);

            {
                foreach($instituicoes as $instituicao){
                    foreach($this->simulacao[$instituicao] as $simulacao){
                        if(\in_array($simulacao['convenio'], $convenios))

                            array_push($arrayAux, $simulacao);
                    }
                    $this->simulacao[$instituicao] = $arrayAux;
                    $arrayAux = [];
                }
            }
        }
        return $this;
    }

    private function filtrarParcela(array $instituicoes, int $parcela) : self
    {
        if(!count($instituicoes))
            $instituicoes = ["PAN", "OLE", "BMG"];

        if (!is_null($parcela)){
            $arrayAux = [];
            $teste = $parcela;
                foreach($instituicoes as $instituicao){
                    foreach($this->simulacao[$instituicao] as $simulacao){
                        if($simulacao['parcelas'] == $parcela)
                            array_push($arrayAux, $simulacao);                      
                        $this->simulacao[$instituicao] = $arrayAux;
                    }
                    $arrayAux = [];
                }
            
        }
        return $this;
    }
}
