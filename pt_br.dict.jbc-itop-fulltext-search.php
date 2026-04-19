<?php
/**
 * @copyright Copyright (C) 2010-2023 JBC / iTop
 * @license   http://opensource.org/licenses/AGPL-3.0
 */
Dict::Add('PT BR', 'Brazilian', 'pt br', array(
	'JbcItopFulltextSearch:PageTitle' => 'Busca (fulltext)',
	'JbcItopFulltextSearch:PageTitle+' => 'Busca global usando índice MySQL FULLTEXT (jbc-itop-fulltext-search).',
	'JbcItopFulltextSearch:Disabled' => 'O módulo de busca fulltext está desativado na configuração.',
	'JbcItopFulltextSearch:SearchError' => 'Não foi possível concluir a busca fulltext. Veja o log para detalhes.',

	'Menu:JbcFulltextRebuildMenu' => 'Reconstruir índice FULLTEXT da busca',
	'Menu:JbcFulltextRebuildMenu+' => 'Esvaziar e reconstruir a tabela auxiliar MySQL FULLTEXT da busca global.',
	'JbcItopFulltextSearch:RebuildTitle' => 'Reconstruir índice FULLTEXT',
	'JbcItopFulltextSearch:RebuildTitle+' => 'Reconstruir a tabela auxiliar MySQL FULLTEXT da busca global.',
	'JbcItopFulltextSearch:RebuildIntro' => 'Esta operação esvazia a tabela de índice fulltext e reindexa todos os objetos elegíveis. Pode demorar vários minutos.',
	'JbcItopFulltextSearch:RebuildSubmit' => 'Iniciar reconstrução',
	'JbcItopFulltextSearch:RebuildDone' => 'Reconstrução concluída. Objetos indexados: %1$s. Duração: %2$s s.',
	'JbcItopFulltextSearch:RebuildFailed' => 'A reconstrução falhou. Veja o log da aplicação.',
	'JbcItopFulltextSearch:RebuildForbidden' => 'Não tem permissão para esta ação (apenas administradores).',
));
